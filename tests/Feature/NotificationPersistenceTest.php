<?php

namespace Tests\Feature;

use App\Services\NotificationPersistence;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Covers the DATABASE channel: the payload contract produced by
 * NotificationPersistence::normalizePayload() plus the actual rows written to
 * the `notifications` / `notification_translations` tables for every type.
 */
class NotificationPersistenceTest extends TestCase
{
    use DatabaseTransactions;

    private function makeClient(string $locale = 'en', ?string $token = null): int
    {
        $unique = $token ?? ('tok_' . uniqid());

        return DB::table('clients')->insertGetId([
            'full_name'  => 'Test ' . $locale,
            'email'      => $unique . '@example.test',
            'api_token'  => $unique,
            'fb_token'   => 'fcm_' . $unique,
            'locale'     => $locale,
            'timezone'   => 'Africa/Cairo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @dataProvider payloadProvider
     */
    #[DataProvider('payloadProvider')]
    public function test_normalize_payload_contract(
        array $input,
        string $type,
        string $id,
        string $postId,
        string $matchId,
        ?string $screen
    ): void {
        $out = NotificationPersistence::normalizePayload($input);

        $this->assertSame($type, $out['type'], 'type');
        $this->assertSame($id, $out['id'] ?? '', 'id');
        $this->assertSame($postId, $out['post_id'], 'post_id');
        $this->assertSame($matchId, $out['match_id'], 'match_id');

        if ($screen !== null) {
            $this->assertSame($screen, $out['screen'], 'screen');
        }
    }

    public static function payloadProvider(): array
    {
        return [
            'post'          => [['type' => 'post', 'id' => '45'], 'post', '45', '45', '', null],
            'goal'          => [['type' => 'goal', 'match_id' => '123', 'fixture_id' => '456', 'team_id' => '1'], 'goal', '123', '', '123', null],
            'events'        => [['type' => 'events', 'match_id' => '123'], 'events', '123', '', '123', null],
            'match_status'  => [['type' => 'match_status', 'match_id' => '123'], 'match_status', '123', '', '123', null],
            'reminder'      => [['type' => 'reminder', 'match_id' => '123'], 'reminder', '123', '', '123', null],
            'team'          => [['type' => 'team', 'team_id' => '33'], 'team', '33', '', '', '1'],
            'league'        => [['type' => 'league', 'competition_id' => '5'], 'league', '5', '', '', '1'],
            'announcement'  => [['type' => 'announcement', 'url' => 'matches'], 'announcement', '', '', '', null],
            'explicit screen 0 preserved' => [['type' => 'team', 'team_id' => '33', 'screen' => '0'], 'team', '33', '', '', '0'],
            'default type is reminder'    => [[], 'reminder', '', '', '', null],
        ];
    }

    public function test_persist_writes_one_row_per_client_and_both_locales(): void
    {
        $en = $this->makeClient('en');
        $ar = $this->makeClient('ar');

        (new NotificationPersistence())->persist([$en, $ar], [
            'title_ar' => 'عنوان',
            'title_en' => 'Title',
            'body_ar'  => 'نص',
            'body_en'  => 'Body',
            'image'    => 'http://img/logo.png',
            'type'     => 'match_status',
            'payload'  => ['type' => 'match_status', 'match_id' => '9'],
        ]);

        $rows = DB::table('notifications')->whereIn('client_id', [$en, $ar])->orderBy('client_id')->get();
        $this->assertCount(2, $rows);

        foreach ($rows as $row) {
            $this->assertSame('match_status', $row->type);
            $this->assertSame('http://img/logo.png', $row->image);
            $this->assertSame(0, (int) $row->is_read);

            $data = json_decode($row->data, true);
            $this->assertSame('match_status', $data['type']);
            $this->assertSame('9', $data['match_id']);
            $this->assertSame('9', $data['id']);

            $translations = DB::table('notification_translations')
                ->where('notification_id', $row->id)
                ->pluck('title', 'locale')
                ->all();
            $this->assertArrayHasKey('ar', $translations);
            $this->assertArrayHasKey('en', $translations);
            $this->assertSame('عنوان', $translations['ar']);
            $this->assertSame('Title', $translations['en']);

            $bodies = DB::table('notification_translations')
                ->where('notification_id', $row->id)
                ->pluck('body', 'locale')
                ->all();
            $this->assertSame('نص', $bodies['ar']);
            $this->assertSame('Body', $bodies['en']);
        }
    }

    public function test_persist_accepts_notify_key_and_derives_type_from_column(): void
    {
        $client = $this->makeClient('en');

        (new NotificationPersistence())->persist([$client], [
            'title'  => 'T',
            'body'   => 'B',
            'notify' => ['type' => 'goal', 'match_id' => '55'],
        ]);

        $row = DB::table('notifications')->where('client_id', $client)->first();
        $this->assertNotNull($row);
        $this->assertSame('goal', $row->type);

        $data = json_decode($row->data, true);
        $this->assertSame('goal', $data['type']);
        $this->assertSame('55', $data['match_id']);
    }

    public function test_persist_is_a_noop_for_empty_or_null_client_list(): void
    {
        $before = DB::table('notifications')->count();

        $persistence = new NotificationPersistence();
        $persistence->persist([], ['title' => 'x', 'body' => 'y', 'type' => 'reminder']);
        $persistence->persist([null, null], ['title' => 'x', 'body' => 'y', 'type' => 'reminder']);

        $this->assertSame($before, DB::table('notifications')->count());
    }

    public function test_persist_from_tokens_only_matches_known_tokens(): void
    {
        $client = $this->makeClient('en', 'token-known');

        (new NotificationPersistence())->persistFromTokens(['fcm_token-known', 'fcm-does-not-exist'], [
            'title'  => 'T',
            'body'   => 'B',
            'type'   => 'reminder',
            'notify' => ['type' => 'reminder', 'match_id' => '1'],
        ]);

        $this->assertSame(1, DB::table('notifications')->where('client_id', $client)->count());
    }

    public function test_every_declared_type_is_persisted_verbatim(): void
    {
        $client = $this->makeClient('en');

        foreach (\App\Models\Notification::TYPES as $type) {
            (new NotificationPersistence())->persist([$client], [
                'title'  => 'T-' . $type,
                'body'   => 'B',
                'type'   => $type,
                'notify' => ['type' => $type],
            ]);
        }

        $types = DB::table('notifications')->where('client_id', $client)->pluck('type')->all();
        $this->assertEqualsCanonicalizing(\App\Models\Notification::TYPES, $types);
    }
}
