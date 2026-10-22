<?php

namespace App\Console\Commands;

use App\Models\Matche;
use App\Models\MatchEvent;
use App\Models\MatchEventTranslation;
use App\Models\Player;
use App\Models\Setting;
use App\Models\Team;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RestoreMatchEvents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'events:restore
        {--days=60 : Only restore finished matches from the last N days (0 = ALL matches, any date)}
        {--limit=500 : Max number of fixtures to fetch (0 = no limit)}
        {--sleep=150 : Milliseconds to sleep between API calls}
        {--dry-run : Only list the matches that would be restored, no API calls}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Re-fetch finished-match events (goals, cards ...) from the football API to restore data deleted by the old clear:events command. Use --days=0 for every match regardless of date. Restored rows are marked AUTO so no notification is re-sent.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $setting = Setting::find(1);
        if (!$setting || !$setting->football_key) {
            $this->error('No football API key configured.');
            return 1;
        }

        $days  = (int) $this->option('days');
        $limit = max(0, (int) $this->option('limit'));
        $sleep = max(0, (int) $this->option('sleep'));
        $dry   = (bool) $this->option('dry-run');

        // Finished matches without any goal event — their score is currently
        // 0-0 because the old clear:events command deleted the goals.
        // --days=0 restores every match in the table, regardless of date.
        $query = Matche::query()
            ->whereNotNull('fixture_id')
            ->whereIn('status', ['FT', 'AET', 'PEN'])
            ->whereDoesntHave('events', fn ($q) => $q->where('status', 'like', 'goal%'))
            ->orderByDesc('match_date');

        if ($days > 0) {
            $query->where('match_date', '>=', now()->subDays($days));
        }

        if ($limit > 0) {
            $query->limit($limit);
        }

        $matches = $query->get(['id', 'fixture_id', 'team1_id', 'team2_id', 'match_date', 'status']);

        $this->info('Matches to restore: ' . $matches->count() . ($days > 0 ? ' (last ' . $days . ' days)' : ' (ALL dates)'));

        if ($dry) {
            foreach ($matches->take(20) as $m) {
                $this->line(sprintf('  match %d | fixture %d | %s | %s', $m->id, $m->fixture_id, $m->status, $m->match_date));
            }
            if ($matches->count() > 20) {
                $this->line('  ... and ' . ($matches->count() - 20) . ' more');
            }
            return 0;
        }

        $fixturesDone = 0;
        $eventsMade   = 0;
        $goalsMade    = 0;

        foreach ($matches as $m) {
            try {
                $res = Http::withHeaders([
                    'x-rapidapi-key' => $setting->football_key,
                ])->get('https://v3.football.api-sports.io/fixtures/events', [
                    'fixture' => $m->fixture_id,
                ])->json('response') ?? [];

                if (empty($res)) {
                    if ($sleep > 0) usleep($sleep * 1000);
                    continue;
                }

                // Fingerprints of what is already stored (never duplicate rows)
                $existing = MatchEvent::where('match_id', $m->id)
                    ->get(['minute', 'status', 'player_id'])
                    ->map(fn ($e) => $e->minute . '|' . $e->status . '|' . $e->player_id)
                    ->flip()
                    ->toArray();

                foreach ($res as $apiEvent) {
                    $status = $this->normalizeStatus($apiEvent);
                    if ($status === null) {
                        continue;
                    }

                    $apiTeamId  = $apiEvent['team']['id'] ?? null;
                    $apiTeamName = $apiEvent['team']['name'] ?? null;
                    $apiLogo    = $apiEvent['team']['logo'] ?? null;
                    $playerId   = $apiEvent['player']['id'] ?? null;
                    $playerName = $apiEvent['player']['name'] ?? 'Unknown';
                    $minute     = $apiEvent['time']['elapsed'] ?? null;

                    if ($apiTeamId === null || $playerId === null || $minute === null) {
                        continue;
                    }

                    $fingerprint = $minute . '|' . $status . '|' . $playerId;
                    if (isset($existing[$fingerprint])) {
                        continue;
                    }
                    $existing[$fingerprint] = true;

                    $team = Team::where('team_id', $apiTeamId)->first();
                    if (!$team) {
                        $team = Team::create(['team_id' => $apiTeamId, 'image' => $apiLogo]);
                        TeamTranslation::create(['name' => $apiTeamName, 'team_id' => $team->id, 'locale' => 'en']);
                        TeamTranslation::create(['name' => $apiTeamName, 'team_id' => $team->id, 'locale' => 'ar']);
                    }

                    $player = Player::where('player_id', $playerId)->first();
                    if (!$player) {
                        // Name comes from the events payload itself — no extra
                        // players API call needed.
                        $player = Player::create([
                            'en'               => ['first_name' => $playerName, 'last_name' => ''],
                            'ar'               => ['first_name' => $playerName, 'last_name' => ''],
                            'player_id'        => $playerId,
                            'team_id'          => $team->id,
                            'football_team_id' => $apiTeamId,
                        ]);
                    }

                    // event_type = AUTO = "already notified", so the live
                    // events pipeline never re-pushes restored events.
                    $event = MatchEvent::create([
                        'minute'      => $minute,
                        'status'      => $status,
                        'player_name' => $playerName,
                        'player_id'   => $player->id,
                        'team_id'     => $team->id,
                        'match_id'    => $m->id,
                        'event_type'  => 'AUTO',
                    ]);

                    MatchEventTranslation::create(['match_event_id' => $event->id, 'description' => $status, 'locale' => 'en']);
                    MatchEventTranslation::create(['match_event_id' => $event->id, 'description' => $status, 'locale' => 'ar']);

                    $eventsMade++;
                    if (str_starts_with($status, 'goal')) {
                        $goalsMade++;
                    }
                }

                $fixturesDone++;
            } catch (\Exception $e) {
                Log::error('events:restore failed', [
                    'match_id'    => $m->id,
                    'fixture_id'  => $m->fixture_id,
                    'error'       => $e->getMessage(),
                ]);
            }

            if ($sleep > 0) usleep($sleep * 1000);
        }

        $summary = sprintf('events:restore: %d fixtures, %d events (%d goals).', $fixturesDone, $eventsMade, $goalsMade);
        Log::info($summary);
        $this->info($summary);

        return 0;
    }

    /**
     * Map an API-Football event to the "{type}-{detail}" convention used in
     * match_events (e.g. "goal-normal goal", "goal-penalty", "goal-own goal")
     * so the existing score logic keeps working. Returns null for events we
     * do not store (subst / var / unknown).
     */
    protected function normalizeStatus(array $apiEvent): ?string
    {
        $rawType   = strtolower($apiEvent['type'] ?? '');
        $rawDetail = strtolower($apiEvent['detail'] ?? '');

        if ($rawType === '' || $rawType === 'subst' || $rawType === 'var') {
            return null;
        }

        if ($rawType === 'goal') {
            if (str_contains($rawDetail, 'own')) {
                return 'goal-own goal';
            }
            if (str_contains($rawDetail, 'missed')) {
                return 'goal-missed penalty';
            }
            if (str_contains($rawDetail, 'cancelled')) {
                return 'goal cancelled';
            }
            if (str_contains($rawDetail, 'penalty')) {
                return 'goal-penalty';
            }
            return 'goal-normal goal';
        }

        if ($rawType === 'card') {
            return str_contains($rawDetail, 'red') ? 'red card' : 'yellow card';
        }

        return $rawDetail !== '' ? $rawDetail : $rawType;
    }
}
