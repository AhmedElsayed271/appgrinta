<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * READ-ONLY report: finds clients without an email and reports where that
 * email could be recovered from. It never writes to the database.
 *
 * A client's email can only be recovered from data we already have:
 *  - social_id: some old app versions stored the email in this column;
 *  - device:    the same FCM token also belongs to an account with an email;
 *  - name:      the same full name as an account with an email (weak clue).
 *
 * Everything else is unrecoverable offline and needs the client to log in
 * again (see AuthController::backfillEmail()).
 */
class RecoverClientEmails extends Command
{
    protected $signature = 'clients:recover-emails
                            {--csv= : Path of the CSV report (default: storage/app/reports/…)}
                            {--only= : Only one evidence type: social_id, device or name}';

    protected $description = 'Read-only report of clients without an email and where it could be recovered from';

    private const EVIDENCE_RANK = [
        'social_id' => 3,
        'device'    => 2,
        'name'      => 1,
    ];

    public function handle(): int
    {
        $only = $this->option('only');

        if ($only !== null && !array_key_exists($only, self::EVIDENCE_RANK)) {
            $this->error("Invalid --only value [{$only}]. Use: social_id, device, name.");

            return self::FAILURE;
        }

        $clients = DB::table('clients')
            ->select('id', 'full_name', 'email', 'social_id', 'fb_token', 'created_at')
            ->orderBy('id')
            ->get();

        $emailsInUse   = [];
        $ownersByToken = [];
        $ownersByName  = [];

        foreach ($clients as $client) {
            $email = $this->clean($client->email);

            if ($email === null) {
                continue;
            }

            $emailsInUse[mb_strtolower($email)] = $client;

            $token = $this->clean($client->fb_token);
            if ($token !== null) {
                $ownersByToken[$token][] = $client;
            }

            $name = $this->clean($client->full_name);
            if ($name !== null) {
                $ownersByName[mb_strtolower($name)][] = $client;
            }
        }

        $missing = $clients->filter(fn ($client) => $this->clean($client->email) === null);

        $rows    = [];
        $stats   = ['social_id' => 0, 'device' => 0, 'name' => 0, 'conflict' => 0];
        $byConfidence = ['high' => 0, 'low' => 0];

        foreach ($missing as $client) {
            $candidate = $this->candidateFor($client, $emailsInUse, $ownersByToken, $ownersByName);

            if ($candidate === null) {
                continue;
            }

            if ($only !== null && !in_array($only, $candidate['evidence'], true)) {
                continue;
            }

            foreach (array_keys(self::EVIDENCE_RANK) as $type) {
                if (in_array($type, $candidate['evidence'], true)) {
                    $stats[$type]++;
                }
            }

            if ($candidate['conflict_id'] !== null) {
                $stats['conflict']++;
            }

            $byConfidence[$candidate['confidence']]++;

            $rows[] = [
                'client_id'   => $client->id,
                'full_name'   => $client->full_name,
                'created_at'  => $client->created_at,
                'social_id'   => $client->social_id,
                'evidence'    => implode('+', $candidate['evidence']),
                'confidence'  => $candidate['confidence'],
                'suggested'   => $candidate['suggested_email'],
                'candidates'  => implode(' | ', array_keys($candidate['candidates'])),
                'conflict_id' => $candidate['conflict_id'],
                'note'        => implode('; ', $candidate['notes']),
            ];
        }

        usort($rows, function (array $a, array $b) {
            return [$b['confidence'] === 'high', $a['client_id']] <=> [$a['confidence'] === 'high', $b['client_id']];
        });

        $csvPath = $this->writeCsv($rows);

        $this->newLine();
        $this->info('Read-only report — the database was NOT modified.');
        $this->table(['', 'count'], [
            ['clients total', $clients->count()],
            ['missing email', $missing->count()],
            ['recoverable candidates', count($rows)],
            ['  via social_id', $stats['social_id']],
            ['  via device token', $stats['device']],
            ['  via full name', $stats['name']],
            ['  high confidence', $byConfidence['high']],
            ['  low confidence', $byConfidence['low']],
            ['  unique-email conflicts', $stats['conflict']],
            ['not recoverable offline', $missing->count() - count($rows)],
        ]);

        if ($rows !== []) {
            $preview = array_slice(array_filter($rows, fn ($row) => $row['confidence'] === 'high'), 0, 10);

            if ($preview !== []) {
                $this->table(
                    ['id', 'name', 'evidence', 'suggested email', 'conflict'],
                    array_map(fn ($row) => [
                        $row['client_id'],
                        $row['full_name'],
                        $row['evidence'],
                        $row['suggested'],
                        $row['conflict_id'] ?? '-',
                    ], $preview)
                );
            }
        }

        $this->line("CSV: {$csvPath}");

        return self::SUCCESS;
    }

    /**
     * @return array{evidence: array<int,string>, suggested_email: ?string, confidence: string, conflict_id: ?int, candidates: array<string,string>, notes: array<int,string>}|null
     */
    private function candidateFor($client, array $emailsInUse, array $ownersByToken, array $ownersByName): ?array
    {
        $evidence   = [];
        $candidates = [];
        $notes      = [];
        $confidence = 'low';
        $conflictId = null;

        // 1) social_id is the email itself (old app behaviour).
        $socialId = $this->clean($client->social_id);
        if ($socialId !== null && filter_var($socialId, FILTER_VALIDATE_EMAIL)) {
            $evidence[] = 'social_id';
            $candidates[$socialId] = 'social_id';
            $confidence = 'high';

            $owner = $emailsInUse[mb_strtolower($socialId)] ?? null;
            if ($owner !== null && (int) $owner->id !== (int) $client->id) {
                $conflictId = (int) $owner->id;
                $notes[] = "email already used by client #{$owner->id}";
            }
        }

        // 2) The same device token also belongs to an account with an email.
        $token = $this->clean($client->fb_token);
        if ($token !== null && isset($ownersByToken[$token])) {
            $evidence[] = 'device';
            $sameName   = false;

            foreach ($ownersByToken[$token] as $owner) {
                $candidates[$this->clean($owner->email)] = 'device';

                if ($this->sameName($client->full_name, $owner->full_name)) {
                    $sameName = true;
                }
            }

            if ($sameName) {
                $confidence = 'high';
            }

            $notes[] = count($ownersByToken[$token]) . ' account(s) share this device token';
        }

        // 3) Same full name as an account with an email (weak clue).
        $name = $this->clean($client->full_name);
        if ($name !== null && isset($ownersByName[mb_strtolower($name)])) {
            $evidence[] = 'name';

            foreach ($ownersByName[mb_strtolower($name)] as $owner) {
                $candidates[$this->clean($owner->email)] = 'name';
            }

            $notes[] = 'same full name as another account (weak)';
        }

        if ($evidence === []) {
            return null;
        }

        // Prefer the candidate backed by the strongest evidence.
        $suggested = null;
        $bestRank  = 0;

        foreach ($candidates as $email => $source) {
            $rank = self::EVIDENCE_RANK[$source] ?? 0;

            if ($rank > $bestRank) {
                $bestRank  = $rank;
                $suggested = $email;
            }
        }

        return [
            'evidence'        => $evidence,
            'suggested_email' => $suggested,
            'confidence'      => $confidence,
            'conflict_id'     => $conflictId,
            'candidates'      => $candidates,
            'notes'           => $notes,
        ];
    }

    private function writeCsv(array $rows): string
    {
        $path = $this->option('csv')
            ?: storage_path('app/reports/clients-recover-emails-' . now()->format('Y-m-d-His') . '.csv');

        File::ensureDirectoryExists(dirname($path));

        $handle = fopen($path, 'w');

        // UTF-8 BOM so Excel opens the Arabic names correctly.
        fwrite($handle, "\xEF\xBB\xBF");

        fputcsv($handle, [
            'client_id', 'full_name', 'created_at', 'social_id', 'evidence',
            'confidence', 'suggested_email', 'candidate_emails', 'conflict_client_id', 'note',
        ]);

        foreach ($rows as $row) {
            fputcsv($handle, [
                $row['client_id'], $row['full_name'], $row['created_at'], $row['social_id'],
                $row['evidence'], $row['confidence'], $row['suggested'], $row['candidates'],
                $row['conflict_id'], $row['note'],
            ]);
        }

        fclose($handle);

        return $path;
    }

    private function sameName(?string $a, ?string $b): bool
    {
        $a = mb_strtolower(trim((string) $a));
        $b = mb_strtolower(trim((string) $b));

        return $a !== '' && $a === $b;
    }

    private function clean($value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
