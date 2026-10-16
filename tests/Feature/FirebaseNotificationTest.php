<?php

namespace Tests\Feature;

use App\Jobs\SendFirebaseNotifications;
use App\Services\FirebaseService;
use App\Traits\Notify;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Covers the FIREBASE channel: NotificationPersistence normalization inside the
 * Notify trait, the queued job and the actual FCM v1 HTTP payload built by
 * FirebaseService (with the network faked).
 */
class FirebaseNotificationTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        // Avoid a real Google OAuth round-trip to obtain the FCM access token.
        cache()->put('firebase_access_token', 'fake-access-token', 3500);
    }

    private function makeClient(string $token, string $locale = 'en'): int
    {
        return DB::table('clients')->insertGetId([
            'full_name'  => 'Test',
            'email'      => $token . '@example.test',
            'api_token'  => $token,
            'fb_token'   => $token,
            'locale'     => $locale,
            'timezone'   => 'Africa/Cairo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    // ─── Notify trait ────────────────────────────────────────────────────────

    public function test_trait_normalizes_payload_persists_and_dispatches_job(): void
    {
        Bus::fake();

        $obj = new class {
            use Notify;

            public array $persisted = [];

            protected function persistInApp(array $tokens, array $data = []): void
            {
                $this->persisted[] = ['tokens' => $tokens, 'data' => $data];
            }
        };

        $obj->topicNotifyByFirebaseTokens(['t1', 't2', ''], [
            'title'  => 'Title',
            'body'   => 'Body',
            'notify' => ['match_id' => '7'],
        ]);

        $this->assertCount(1, $obj->persisted);
        $this->assertSame(['t1', 't2'], $obj->persisted[0]['tokens']);

        $notify = $obj->persisted[0]['data']['notify'];
        $this->assertSame('reminder', $notify['type'], 'missing type must default to reminder');
        $this->assertSame('7', $notify['id']);
        $this->assertSame('7', $notify['match_id']);

        Bus::assertDispatchedAfterResponse(SendFirebaseNotifications::class);
    }

    public function test_trait_does_nothing_for_empty_token_list(): void
    {
        Bus::fake();

        $obj = new class {
            use Notify;

            public array $persisted = [];

            protected function persistInApp(array $tokens, array $data = []): void
            {
                $this->persisted[] = ['tokens' => $tokens, 'data' => $data];
            }
        };

        $obj->topicNotifyByFirebaseTokens([], ['title' => 'T', 'body' => 'B', 'notify' => ['type' => 'goal']]);

        $this->assertCount(0, $obj->persisted);
        Bus::assertNothingDispatched();
    }

    public function test_trait_persists_to_database_for_real(): void
    {
        Bus::fake();

        $clientId = DB::table('clients')->insertGetId([
            'full_name'  => 'Real',
            'email'      => 'real_' . uniqid() . '@example.test',
            'api_token'  => 'real-' . uniqid(),
            'fb_token'   => 'real-fcm-' . uniqid(),
            'locale'     => 'en',
            'timezone'   => 'Africa/Cairo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $token = DB::table('clients')->where('id', $clientId)->value('fb_token');

        $obj = new class {
            use Notify;
        };

        $obj->topicNotifyByFirebaseTokens([$token], [
            'title'  => 'Bell title',
            'body'   => 'Bell body',
            'notify' => ['type' => 'goal', 'match_id' => '99'],
        ]);

        $row = DB::table('notifications')->where('client_id', $clientId)->first();
        $this->assertNotNull($row, 'the trait must also write the in-app bell row');
        $this->assertSame('goal', $row->type);
    }

    // ─── FirebaseService transport ───────────────────────────────────────────

    private function fakeFcm(int $status = 200, array $body = ['name' => 'projects/x/messages/1']): void
    {
        cache()->put('firebase_access_token', 'fake-token', 3500);
        Http::fake(['fcm.googleapis.com/*' => Http::response($body, $status)]);
    }

    public function test_service_sends_correct_fcm_v1_payload(): void
    {
        $this->fakeFcm();

        (new FirebaseService())->sendNotification(['tok-1', 'tok-2'], [
            'title'  => 'Title',
            'body'   => 'Body',
            'image'  => 'http://img/logo.png',
            'notify' => ['type' => 'goal', 'match_id' => '42', 'count' => 3],
        ]);

        Http::assertSentCount(2);

        $request = null;
        Http::assertSent(function ($req) use (&$request) {
            $request = $req;
            return true;
        });

        $payload = $request->data();
        $this->assertSame('Title', $payload['message']['notification']['title']);
        $this->assertSame('Body', $payload['message']['notification']['body']);
        $this->assertSame('http://img/logo.png', $payload['message']['notification']['image']);
        $this->assertSame('goal', $payload['message']['data']['type']);
        $this->assertSame('42', $payload['message']['data']['match_id']);
        $this->assertSame('3', $payload['message']['data']['count'], 'non-string data must be cast to string');
        $this->assertSame('Title', $payload['message']['data']['title'], 'title is mirrored into data');

        $this->assertContains($payload['message']['token'], ['tok-1', 'tok-2']);
        $this->assertArrayHasKey('android', $payload['message']);
        $this->assertArrayHasKey('apns', $payload['message']);
    }

    public function test_service_attaches_per_token_notification_id(): void
    {
        $this->fakeFcm();

        (new FirebaseService())->sendNotification(
            ['tok-a', 'tok-b'],
            [
                'title'  => 'T',
                'body'   => 'B',
                'notify' => ['type' => 'goal', 'match_id' => '42'],
            ],
            ['tok-a' => 501, 'tok-b' => 502]
        );

        $dataByToken = [];
        Http::assertSent(function ($req) use (&$dataByToken) {
            $message = $req->data()['message'];
            $dataByToken[$message['token']] = $message['data'];

            return true;
        });

        $this->assertSame('501', $dataByToken['tok-a']['notification_id']);
        $this->assertSame('502', $dataByToken['tok-b']['notification_id']);
        // Existing structure is untouched.
        $this->assertSame('goal', $dataByToken['tok-a']['type']);
        $this->assertSame('42', $dataByToken['tok-a']['match_id']);
    }

    public function test_trait_passes_persisted_notification_id_into_job(): void
    {
        Bus::fake();

        $obj = new class {
            use Notify;
        };

        $token    = 'tok-' . uniqid();
        $clientId = $this->makeClient($token);

        $obj->topicNotifyByFirebaseTokens([$token], [
            'title'  => 'T',
            'body'   => 'B',
            'notify' => ['type' => 'goal', 'match_id' => '7'],
        ]);

        $row = DB::table('notifications')->where('client_id', $clientId)->where('type', 'goal')->first();
        $this->assertNotNull($row, 'a bell row must be written');

        Bus::assertDispatchedAfterResponse(SendFirebaseNotifications::class, function ($job) use ($token, $row) {
            $ref  = new \ReflectionObject($job);
            $prop = $ref->getProperty('notificationIds');
            $prop->setAccessible(true);
            $map = $prop->getValue($job);

            return isset($map[$token]) && (int) $map[$token] === (int) $row->id;
        });
    }

    public function test_service_nulls_unregistered_tokens(): void
    {
        $clientId = DB::table('clients')->insertGetId([
            'full_name'  => 'Stale',
            'email'      => 'stale_' . uniqid() . '@example.test',
            'api_token'  => 'stale-' . uniqid(),
            'fb_token'   => 'stale-token',
            'locale'     => 'en',
            'timezone'   => 'Africa/Cairo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Http::fake([
            'fcm.googleapis.com/*' => Http::response([
                'error' => ['details' => [['errorCode' => 'UNREGISTERED']]],
            ], 404),
        ]);

        (new FirebaseService())->sendNotification(['stale-token'], [
            'title' => 'T', 'body' => 'B', 'notify' => ['type' => 'goal'],
        ]);

        $this->assertNull(DB::table('clients')->where('id', $clientId)->value('fb_token'));
    }

    public function test_job_chunks_and_sends_every_token(): void
    {
        $this->fakeFcm();

        $tokens = ['a', 'b', 'c', 'd'];
        (new SendFirebaseNotifications($tokens, [
            'title' => 'T', 'body' => 'B', 'notify' => ['type' => 'reminder'],
        ]))->handle();

        Http::assertSentCount(4);
    }
}
