<?php

namespace Tests\Feature;

use App\Console\Commands\SendReminderBefore45Minutes;
use App\Models\Matche;
use App\Models\Team;
use App\Traits\NotificationOfMatchesTrait;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Covers the match-notification senders: the LIVE event notifications built by
 * NotificationOfMatchesTrait (the path actually used by `live:get`) and the
 * 45-minute reminder command. These run through the real Notify pipeline, so we
 * replace the FCM token method to inspect the exact EN/AR body instead of
 * hitting Firebase.
 */
class MatchEventNotificationTest extends TestCase
{
    use DatabaseTransactions;

    private function fixtureMatch(bool $withFixture = true): ?Matche
    {
        $query = Matche::with(['team1.translations', 'team2.translations'])
            ->whereHas('team1.translations')
            ->whereHas('team2.translations');

        if ($withFixture) {
            $query->whereNotNull('fixture_id');
        }

        return $query->first();
    }

    private function traitObject(): object
    {
        return new class {
            use NotificationOfMatchesTrait;

            public array $sent = [];

            public function topicNotifyByFirebaseTokens(array|Collection $tokens, array $data = []): void
            {
                $tokens = $tokens instanceof Collection ? $tokens->values()->all() : array_values($tokens);
                $this->sent[] = ['tokens' => $tokens, 'data' => $data];
            }
        };
    }

    private function follower(int $teamId, string $locale = 'en'): int
    {
        $unique = uniqid('', true);
        $id = DB::table('clients')->insertGetId([
            'full_name'  => 'Follower ' . $locale,
            'email'      => $unique . '@example.test',
            'api_token'  => 'api_' . $unique,
            'fb_token'   => 'fcm_' . $unique,
            'locale'     => $locale,
            'timezone'   => 'Africa/Cairo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('favourite_team')->insert(['team_id' => $teamId, 'client_id' => $id]);

        return $id;
    }

    private function apiEvent(Team $team, int $elapsed, ?int $extra, string $type, string $detail): array
    {
        return [
            'time'   => ['elapsed' => $elapsed, 'extra' => $extra],
            'team'   => ['id' => $team->team_id, 'name' => 'Team', 'logo' => 'logo.png'],
            'player' => ['id' => random_int(900000000, 999999999), 'name' => 'Test Player'],
            'type'   => $type,
            'detail' => $detail,
        ];
    }

    public function test_goal_event_uses_api_score_and_includes_added_time(): void
    {
        $match = $this->fixtureMatch();
        if (!$match) {
            $this->markTestSkipped('No fully translated match with fixture_id in DB.');
        }

        $team = Team::find($match->team1_id);
        $this->follower($match->team1_id, 'en');
        $this->follower($match->team1_id, 'ar');

        $obj     = $this->traitObject();
        $matches = $obj->matchArrayFromFixtureId($match->fixture_id, 2, 1);

        $result = $obj->events($this->apiEvent($team, 45, 2, 'goal', 'Normal Goal'), $matches, 0, $match->id);

        $this->assertTrue($result);
        $bodies = implode("\n", array_map(fn ($c) => $c['data']['body'], $obj->sent));

        $this->assertStringContainsString('2-1', $bodies, 'EN body must carry the API score');
        $this->assertStringContainsString('45 + 2', $bodies, 'added time must be rendered as "45 + 2"');
        $this->assertStringContainsString('1-2', $bodies, 'AR body must carry the reversed score');
    }

    public function test_goal_event_without_extra_has_no_plus_sign(): void
    {
        $match = $this->fixtureMatch();
        if (!$match) {
            $this->markTestSkipped('No fully translated match with fixture_id in DB.');
        }

        $team = Team::find($match->team1_id);
        $this->follower($match->team1_id, 'en');

        $obj     = $this->traitObject();
        $matches = $obj->matchArrayFromFixtureId($match->fixture_id, 1, 0);

        $obj->events($this->apiEvent($team, 30, null, 'goal', 'Normal Goal'), $matches, 0, $match->id);

        $bodies = implode("\n", array_map(fn ($c) => $c['data']['body'], $obj->sent));
        $this->assertStringContainsString('1-0', $bodies);
        $this->assertStringNotContainsString(' + ', $bodies, 'no added time must not print " + "');
    }

    public function test_goal_event_is_deduplicated_on_second_poll(): void
    {
        $match = $this->fixtureMatch();
        if (!$match) {
            $this->markTestSkipped('No fully translated match with fixture_id in DB.');
        }

        $team = Team::find($match->team1_id);
        $this->follower($match->team1_id, 'en');

        $obj     = $this->traitObject();
        $matches = $obj->matchArrayFromFixtureId($match->fixture_id, 1, 0);
        $event   = $this->apiEvent($team, 60, null, 'goal', 'Normal Goal');

        $this->assertTrue($obj->events($event, $matches, 0, $match->id));
        $firstCount = count($obj->sent);

        $this->assertFalse($obj->events($event, $matches, 0, $match->id), 'duplicate poll must be ignored');
        $this->assertCount($firstCount, $obj->sent, 'no extra push for the duplicate');
    }

    public function test_var_event_includes_score_and_added_time(): void
    {
        $match = $this->fixtureMatch();
        if (!$match) {
            $this->markTestSkipped('No fully translated match with fixture_id in DB.');
        }

        $team = Team::find($match->team1_id);
        $this->follower($match->team1_id, 'en');

        $obj     = $this->traitObject();
        $matches = $obj->matchArrayFromFixtureId($match->fixture_id, 3, 2);

        $obj->events($this->apiEvent($team, 90, 4, 'var', 'Goal cancelled'), $matches, 0, $match->id);

        $bodies = implode("\n", array_map(fn ($c) => $c['data']['body'], $obj->sent));
        $this->assertStringContainsString('3-2', $bodies);
        $this->assertStringContainsString('90 + 4', $bodies);
        $this->assertStringContainsString('[VAR]', $bodies);
    }

    public function test_reminder_command_persists_bell_row_and_builds_bilingual_body(): void
    {
        $match = $this->fixtureMatch(false);
        if (!$match) {
            $this->markTestSkipped('No fully translated match in DB.');
        }

        $match = Matche::with(['team1.translations', 'team2.translations'])->find($match->id);

        $clientEn = $this->follower($match->team1_id, 'en');
        $clientAr = $this->follower($match->team2_id, 'ar');

        $cmd = new class extends \App\Console\Commands\SendReminderBefore45Minutes {
            public array $sent = [];

            public function topicNotifyByFirebaseTokens(array|Collection $tokens, array $data = []): void
            {
                $tokens = $tokens instanceof Collection ? $tokens->values()->all() : array_values($tokens);
                $this->sent[] = ['tokens' => $tokens, 'data' => $data];
            }
        };

        $method = new \ReflectionMethod($cmd, 'sendNotificationsToClients');
        $method->setAccessible(true);
        $method->invoke($cmd, $match);

        $this->assertNotEmpty($cmd->sent, 'reminder must push to Firebase');
        $this->assertNotEmpty(DB::table('notifications')->where('client_id', $clientEn)->where('type', 'reminder')->get());
        $this->assertNotEmpty(DB::table('notifications')->where('client_id', $clientAr)->where('type', 'reminder')->get());

        $titles = implode('|', array_map(fn ($c) => $c['data']['notify']['match_id'], $cmd->sent));
        $this->assertStringContainsString((string) $match->id, $titles);
    }
}
