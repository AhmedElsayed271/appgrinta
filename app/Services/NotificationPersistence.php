<?php

namespace App\Services;

use App\Models\Client;
use Illuminate\Support\Facades\DB;

class NotificationPersistence
{
    /**
     * Persist an in-app (database) notification for the given clients.
     *
     * @return array<int,int> Map of client_id => notification id.
     */
    public function persist(array $clientIds, array $data): array
    {
        $clientIds = array_values(array_unique(array_filter($clientIds, fn ($id) => $id != null)));

        if (count($clientIds) === 0) {
            return [];
        }

        $notify = $data['notify'] ?? null;

        $payloadSource = $data['payload'] ?? $notify;
        if (is_object($payloadSource) && method_exists($payloadSource, 'toArray')) {
            $payloadSource = $payloadSource->toArray();
        }
        if (is_array($payloadSource)) {
            $payloadSource = self::normalizePayload($payloadSource);
        }

        $type  = $data['type'] ?? (is_array($payloadSource) ? ($payloadSource['type'] ?? 'reminder') : 'reminder');
        $image = $data['image'] ?? null;

        $payload = is_array($payloadSource)
            ? json_encode($payloadSource, JSON_UNESCAPED_UNICODE)
            : null;

        $title   = $data['title'] ?? '';
        $body    = $data['body'] ?? '';
        $titleAr = $data['title_ar'] ?? $title;
        $titleEn = $data['title_en'] ?? $title;
        $bodyAr  = $data['body_ar'] ?? $body;
        $bodyEn  = $data['body_en'] ?? $body;

        $now = now();

        $map = [];

        foreach (array_chunk($clientIds, 500) as $chunk) {
            $chunkMap = DB::transaction(function () use ($chunk, $now, $type, $image, $payload, $titleAr, $titleEn, $bodyAr, $bodyEn) {
                $beforeMax = DB::table('notifications')->max('id') ?? 0;

                $rows = [];
                foreach ($chunk as $clientId) {
                    $rows[] = [
                        'client_id'  => $clientId,
                        'type'       => $type,
                        'image'      => $image,
                        'data'       => $payload,
                        'is_read'    => false,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
                DB::table('notifications')->insert($rows);

                // client_id => freshly created notification id for this batch
                $ids = DB::table('notifications')
                    ->where('id', '>', $beforeMax)
                    ->whereIn('client_id', $chunk)
                    ->pluck('id', 'client_id');

                $tRows = [];
                foreach ($ids as $nid) {
                    $tRows[] = [
                        'notification_id' => $nid,
                        'title'           => $titleAr,
                        'body'            => $bodyAr,
                        'locale'          => 'ar',
                        'created_at'      => $now,
                        'updated_at'      => $now,
                    ];
                    $tRows[] = [
                        'notification_id' => $nid,
                        'title'           => $titleEn,
                        'body'            => $bodyEn,
                        'locale'          => 'en',
                        'created_at'      => $now,
                        'updated_at'      => $now,
                    ];
                }
                DB::table('notification_translations')->insert($tRows);

                return $ids->all();
            });

            $map += $chunkMap;
        }

        return $map;
    }

    /**
     * Ensure the payload exposes a consistent `type` and an `id` the mobile
     * app can navigate with (post / match / team / competition ...).
     */
    public static function normalizePayload(array $payload): array
    {
        $payload['type'] = $payload['type'] ?? 'reminder';

        // League / team notifications always expose a `screen` (same convention
        // as favourite-league → "1") so the app can deep-link. An explicit value
        // supplied by the sender (including "0") is always preserved.
        if ((!isset($payload['screen']) || $payload['screen'] === '') && in_array($payload['type'], ['league', 'team'], true)) {
            $payload['screen'] = '1';
        }

        if (!isset($payload['id']) || $payload['id'] === '') {
            $idKeys = ['post_id', 'match_id', 'fixture_id', 'team_id', 'competition_id', 'player_id', 'notification_id'];
            foreach ($idKeys as $key) {
                if (isset($payload[$key]) && $payload[$key] !== '' && !is_array($payload[$key])) {
                    $payload['id'] = (string) $payload[$key];
                    break;
                }
            }
        }

        $id = isset($payload['id']) ? (string) $payload['id'] : '';

        // Always expose post_id / match_id so the mobile app can branch on them.
        $payload['post_id'] = isset($payload['post_id']) && $payload['post_id'] !== ''
            ? (string) $payload['post_id']
            : (in_array($payload['type'], ['post'], true) ? $id : '');

        $matchTypes = [
            'match_status',
            'goal',
            'events',
            'reminder',
            'match',
            'event',
        ];
        $payload['match_id'] = isset($payload['match_id']) && $payload['match_id'] !== ''
            ? (string) $payload['match_id']
            : (in_array($payload['type'], $matchTypes, true) ? $id : '');

        return $payload;
    }

    /**
     * Persist an in-app (database) notification for the clients owning the
     * given device tokens.
     *
     * @return array<string,int> Map of fb_token => notification id.
     */
    public function persistFromTokens(array $tokens, array $data): array
    {
        $tokens = array_values(array_unique(array_filter($tokens, fn ($token) => $token != null && $token !== '')));

        if (count($tokens) === 0) {
            return [];
        }

        $clients = Client::whereIn('fb_token', $tokens)->get(['id', 'fb_token']);

        $clientMap = $this->persist($clients->pluck('id')->all(), $data);

        $tokenMap = [];
        foreach ($clients as $client) {
            if (isset($clientMap[$client->id])) {
                $tokenMap[$client->fb_token] = $clientMap[$client->id];
            }
        }

        return $tokenMap;
    }
}