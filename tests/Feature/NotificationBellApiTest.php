<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Covers the in-app bell HTTP API (database channel, read side):
 * list / unread-count / mark read / mark all read / delete, plus owner scoping.
 */
class NotificationBellApiTest extends TestCase
{
    use DatabaseTransactions;

    private function client(string $locale = 'en'): array
    {
        $unique = uniqid('', true);
        $id = DB::table('clients')->insertGetId([
            'full_name'  => 'Bell ' . $locale,
            'email'      => $unique . '@example.test',
            'api_token'  => 'bell_' . $unique,
            'fb_token'   => 'fcm_bell_' . $unique,
            'locale'     => $locale,
            'timezone'   => 'Africa/Cairo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$id, DB::table('clients')->where('id', $id)->value('api_token')];
    }

    private function notification(int $clientId, string $type = 'goal', bool $read = false, array $meta = []): int
    {
        $id = DB::table('notifications')->insertGetId([
            'client_id'  => $clientId,
            'type'       => $type,
            'image'      => 'http://img/x.png',
            'data'       => json_encode(array_merge(['type' => $type, 'match_id' => '7'], $meta), JSON_UNESCAPED_UNICODE),
            'is_read'    => $read,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach (['ar' => 'عنوان', 'en' => 'Title'] as $locale => $title) {
            DB::table('notification_translations')->insert([
                'notification_id' => $id,
                'title'           => $title,
                'body'            => 'Body ' . $locale,
                'locale'          => $locale,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }

        return $id;
    }

    private function as(string $token): static
    {
        return $this->withHeaders(['Authorization' => 'Bearer ' . $token]);
    }

    public function test_index_returns_only_own_notifications(): void
    {
        [$mine, $token]     = $this->client('en');
        [$theirs]           = $this->client('en');
        $this->notification($mine, 'goal');
        $this->notification($theirs, 'goal');

        $response = $this->as($token)->getJson('/api/v1/notifications?per_page=15');

        $response->assertOk();
        $response->assertJsonPath('isSuccess', true);

        $items = $response->json('data.data');
        $this->assertCount(1, $items);
        $this->assertSame($mine, (int) DB::table('notifications')->where('id', $items[0]['id'])->value('client_id'));
    }

    public function test_type_filter_is_applied(): void
    {
        [$clientId, $token] = $this->client('en');
        $this->notification($clientId, 'goal');
        $this->notification($clientId, 'match_status');

        $response = $this->as($token)->getJson('/api/v1/notifications?type=match_status');

        $response->assertOk();
        $items = $response->json('data.data');
        $this->assertCount(1, $items);
        $this->assertSame('match_status', $items[0]['type']);
    }

    public function test_unread_count(): void
    {
        [$clientId, $token] = $this->client('en');
        $this->notification($clientId, 'goal', false);
        $this->notification($clientId, 'goal', false);
        $this->notification($clientId, 'goal', true);

        $this->as($token)->getJson('/api/v1/notifications/unread-count')
            ->assertOk()
            ->assertJsonPath('data.count', 2);
    }

    public function test_mark_ids_as_read(): void
    {
        [$clientId, $token] = $this->client('en');
        $a = $this->notification($clientId, 'goal', false);
        $b = $this->notification($clientId, 'goal', false);

        $this->as($token)->postJson('/api/v1/notifications/read', ['ids' => [$a, $b]])
            ->assertOk()
            ->assertJsonPath('data.count', 2);

        $this->assertSame(2, DB::table('notifications')->whereIn('id', [$a, $b])->where('is_read', 1)->count());
    }

    public function test_mark_all_as_read(): void
    {
        [$clientId, $token] = $this->client('en');
        $this->notification($clientId, 'goal', false);
        $this->notification($clientId, 'match_status', false);

        $this->as($token)->postJson('/api/v1/notifications/read-all')
            ->assertOk();

        $this->assertSame(0, DB::table('notifications')->where('client_id', $clientId)->where('is_read', 0)->count());
    }

    public function test_delete_is_owner_scoped(): void
    {
        [$mine, $token] = $this->client('en');
        [$theirs]       = $this->client('en');
        $mineId   = $this->notification($mine, 'goal');
        $theirsId = $this->notification($theirs, 'goal');

        $this->as($token)->deleteJson('/api/v1/notifications/' . $theirsId)->assertOk();
        $this->assertNotNull(DB::table('notifications')->where('id', $theirsId)->first(), 'other client row must survive');

        $this->as($token)->deleteJson('/api/v1/notifications/' . $mineId)->assertOk();
        $this->assertNull(DB::table('notifications')->where('id', $mineId)->first());
    }

    public function test_delete_returns_404_for_missing_notification(): void
    {
        [, $token] = $this->client('en');

        $this->as($token)->deleteJson('/api/v1/notifications/999999999')->assertNotFound();
    }

    public function test_index_paginates_and_reports_meta(): void
    {
        [$clientId, $token] = $this->client('en');
        for ($i = 0; $i < 5; $i++) {
            $this->notification($clientId, 'goal');
        }

        $first = $this->as($token)->getJson('/api/v1/notifications?per_page=2&page=1');
        $first->assertOk();
        $this->assertSame(5, $first->json('data.meta.total'));
        $this->assertSame(2, $first->json('data.meta.per_page'));
        $this->assertSame(1, $first->json('data.meta.current_page'));
        $this->assertCount(2, $first->json('data.data'));

        $second = $this->as($token)->getJson('/api/v1/notifications?per_page=2&page=2');
        $second->assertOk();
        $this->assertSame(2, $second->json('data.meta.current_page'));
        $this->assertCount(2, $second->json('data.data'));

        $ids = array_column($first->json('data.data'), 'id');
        $this->assertNotContains($second->json('data.data.0.id'), $ids, 'pages must not overlap');
    }

    public function test_per_page_is_clamped_to_safe_bounds(): void
    {
        [$clientId, $token] = $this->client('en');
        $this->notification($clientId, 'goal');

        $this->as($token)->getJson('/api/v1/notifications?per_page=1')
            ->assertOk()
            ->assertJsonPath('data.meta.per_page', 2);

        $this->as($token)->getJson('/api/v1/notifications?per_page=9999')
            ->assertOk()
            ->assertJsonPath('data.meta.per_page', 50);
    }

    public function test_missing_current_locale_falls_back_to_arabic(): void
    {
        [$clientId, $token] = $this->client('en');

        $id = DB::table('notifications')->insertGetId([
            'client_id'  => $clientId,
            'type'       => 'goal',
            'image'      => 'http://img/x.png',
            'data'       => json_encode(['type' => 'goal', 'match_id' => '9'], JSON_UNESCAPED_UNICODE),
            'is_read'    => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('notification_translations')->insert([
            'notification_id' => $id,
            'title'           => 'عنوان احتياطي',
            'body'            => 'نص احتياطي',
            'locale'          => 'ar',
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        $response = $this->as($token)->getJson('/api/v1/notifications');

        $response->assertOk();
        $this->assertSame('عنوان احتياطي', $response->json('data.data.0.title'));
        $this->assertSame('نص احتياطي', $response->json('data.data.0.body'));
    }
}
