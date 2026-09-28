<?php

namespace App\Services;

use App\Models\Client;
use Illuminate\Support\Facades\DB;

class NotificationPersistence
{
    public function persist(array $clientIds, array $data): void
    {
        $clientIds = array_values(array_unique(array_filter($clientIds, fn ($id) => $id != null)));

        if (count($clientIds) === 0) {
            return;
        }

        $type    = $data['type'] ?? 'system';
        $image   = $data['image'] ?? null;
        $payload = isset($data['payload']) && is_array($data['payload'])
            ? json_encode($data['payload'], JSON_UNESCAPED_UNICODE)
            : null;

        $titleAr = $data['title_ar'] ?? '';
        $titleEn = $data['title_en'] ?? $data['title_ar'] ?? '';
        $bodyAr  = $data['body_ar'] ?? '';
        $bodyEn  = $data['body_en'] ?? $data['body_ar'] ?? '';

        $now = now();

        foreach (array_chunk($clientIds, 500) as $chunk) {
            DB::transaction(function () use ($chunk, $now, $type, $image, $payload, $titleAr, $titleEn, $bodyAr, $bodyEn) {
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

                $ids = DB::table('notifications')
                    ->where('id', '>', $beforeMax)
                    ->whereIn('client_id', $chunk)
                    ->orderBy('id')
                    ->pluck('id');

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
            });
        }
    }

    public function persistFromTokens(array $tokens, array $data): void
    {
        $tokens = array_values(array_unique(array_filter($tokens, fn ($t) => !empty($t) && is_string($t))));

        if (count($tokens) === 0) {
            return;
        }

        $ids = Client::whereIn('fb_token', $tokens)->pluck('id')->all();

        $this->persist($ids, $data);
    }
}