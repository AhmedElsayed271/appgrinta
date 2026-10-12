<?php

namespace Tests\Feature;

use App\Console\Commands\UpdateMatchesStatus;
use App\Models\Matche;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Tests\TestCase;

class MatchStatusNotificationTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Build the command with sendStatusNotification() overridden so the tests
     * can inspect the exact EN/AR bodies without touching Firebase/DB clients.
     */
    private function captureCommand(): UpdateMatchesStatus
    {
        return new class extends UpdateMatchesStatus {
            public array $sent = [];

            protected function sendStatusNotification(array $display, string $bodyEn, string $bodyAr, string $status = ''): void
            {
                $this->sent[] = [
                    'status'  => $status,
                    'bodyEn'  => $bodyEn,
                    'bodyAr'  => $bodyAr,
                    'display' => $display,
                ];
            }
        };
    }

    private function invoke(object $cmd, string $method, array $args): mixed
    {
        $ref = new \ReflectionMethod($cmd, $method);
        $ref->setAccessible(true);

        return $ref->invokeArgs($cmd, $args);
    }

    private function fixtureMatch(): ?Matche
    {
        return Matche::with(['team1.translations', 'team2.translations'])
            ->whereHas('team1.translations')
            ->whereHas('team2.translations')
            ->first();
    }

    private function data1(int $home, int $away, int $elapsed, ?int $extra = null): array
    {
        return [
            'goals'   => ['home' => $home, 'away' => $away],
            'fixture' => ['status' => ['elapsed' => $elapsed, 'extra' => $extra], 'date' => now()->toDateTimeString()],
            'score'   => ['penalty' => ['home' => 0, 'away' => 0]],
        ];
    }

    /** FIX 1 — score must come from the API, not from match_events counting. */
    public function test_match_display_takes_score_from_api(): void
    {
        $match = $this->fixtureMatch();
        if (!$match) {
            $this->markTestSkipped('No fully translated match available in DB.');
        }

        $cmd     = $this->captureCommand();
        $display = $this->invoke($cmd, 'buildMatchDisplay', [$match, $this->data1(3, 1, 90)]);

        $this->assertSame(3, $display['home_goals']);
        $this->assertSame(1, $display['away_goals']);
    }

    /** FIX 2 — added time (elapsed+extra) appears in the bodies and resets after each phase. */
    public function test_added_time_is_included_in_bodies_and_reset(): void
    {
        $match = $this->fixtureMatch();
        if (!$match) {
            $this->markTestSkipped('No fully translated match available in DB.');
        }

        $cmd = $this->captureCommand();

        // ---- Half time: 1-0 at 45+3 ----
        $match->notificationSent = '1H';
        $match->extra            = 3;
        $match->save();

        $display = $this->invoke($cmd, 'buildMatchDisplay', [$match, $this->data1(1, 0, 45)]);
        $this->invoke($cmd, 'handleStatusNotification', [$match, $display, $this->data1(1, 0, 45), 'HT']);

        $ht = end($cmd->sent);
        $this->assertSame('HT', $ht['status']);
        $this->assertStringContainsString('1-0', $ht['bodyEn']);
        $this->assertStringContainsString('45+3', $ht['bodyEn']);
        $this->assertNull($match->fresh()->extra, 'extra must be reset after HT');

        // ---- Second half: no stale added time ----
        $display = $this->invoke($cmd, 'buildMatchDisplay', [$match, $this->data1(1, 0, 45)]);
        $this->invoke($cmd, 'handleStatusNotification', [$match, $display, $this->data1(1, 0, 45), '2H']);

        $second = end($cmd->sent);
        $this->assertSame('2H', $second['status']);
        $this->assertStringNotContainsString('+', $second['bodyEn'], 'stale added time leaked into 2H');

        // ---- Full time: 3-1 at 90+5 ----
        $match->extra = 5;
        $match->save();

        $display = $this->invoke($cmd, 'buildMatchDisplay', [$match, $this->data1(3, 1, 90)]);
        $this->invoke($cmd, 'handleStatusNotification', [$match, $display, $this->data1(3, 1, 90), 'FT']);

        $ft = end($cmd->sent);
        $this->assertSame('FT', $ft['status']);
        $this->assertStringContainsString('3-1', $ft['bodyEn']);
        $this->assertStringContainsString('90+5', $ft['bodyEn']);
        $this->assertNull($match->fresh()->extra, 'extra must be reset after FT');
    }

    /**
     * End-to-end — the score + added time actually reach the Firebase sender
     * (topicNotifyByFirebaseTokens is the method that POSTs to FCM).
     */
    public function test_notification_reaches_firebase_sender_with_score_and_added_time(): void
    {
        $match = $this->fixtureMatch();
        if (!$match) {
            $this->markTestSkipped('No fully translated match available in DB.');
        }

        $suffix = uniqid('', true);

        $enId = DB::table('clients')->insertGetId([
            'full_name'  => 'Test EN',
            'email'      => "match_status_en_{$suffix}@example.test",
            'api_token'  => "tok_en_{$suffix}",
            'fb_token'   => "fcm_en_{$suffix}",
            'locale'     => 'en',
            'timezone'   => 'Africa/Cairo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $arId = DB::table('clients')->insertGetId([
            'full_name'  => 'Test AR',
            'email'      => "match_status_ar_{$suffix}@example.test",
            'api_token'  => "tok_ar_{$suffix}",
            'fb_token'   => "fcm_ar_{$suffix}",
            'locale'     => 'ar',
            'timezone'   => 'Africa/Cairo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('favourite_team')->insert([
            ['team_id' => $match->team1_id, 'client_id' => $enId],
            ['team_id' => $match->team2_id, 'client_id' => $arId],
        ]);

        $match->notificationSent = '1H,HT,2H';
        $match->extra            = 5;
        $match->save();

        $cmd = new class extends UpdateMatchesStatus {
            public array $fcm = [];

            public function topicNotifyByFirebaseTokens(array|Collection $tokens, array $data = []): void
            {
                $this->fcm[] = [
                    'tokens' => is_array($tokens) ? array_values($tokens) : $tokens->values()->all(),
                    'data'   => $data,
                ];
            }
        };

        $data1   = $this->data1(3, 1, 90);
        $display = $this->invoke($cmd, 'buildMatchDisplay', [$match, $data1]);
        $this->invoke($cmd, 'handleStatusNotification', [$match, $display, $data1, 'FT']);

        $this->assertNotEmpty($cmd->fcm, 'nothing was sent to the Firebase sender');

        $allTokens = [];
        foreach ($cmd->fcm as $call) {
            $allTokens = array_merge($allTokens, $call['tokens']);
            $this->assertSame('match_status', $call['data']['notify']['type']);
            $this->assertSame('match_ended', $call['data']['notify']['sub_type']);
        }

        $bodies = array_map(fn ($c) => $c['data']['body'], $cmd->fcm);
        $joined = implode("\n", $bodies);

        $this->assertContains("fcm_en_{$suffix}", $allTokens);
        $this->assertContains("fcm_ar_{$suffix}", $allTokens);
        $this->assertStringContainsString('3-1', $joined, 'score must reach Firebase');
        $this->assertStringContainsString('90+5', $joined, 'added time must reach Firebase');
    }
}
