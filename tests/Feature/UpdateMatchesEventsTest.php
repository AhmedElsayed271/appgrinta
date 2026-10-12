<?php

namespace Tests\Feature;

use App\Models\Matche;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Covers the (currently dormant, testtest.enable = 0) events:update sender.
 * The full command makes live HTTP calls, so we exercise the notification
 * builder directly and assert score + added-time handling.
 */
class UpdateMatchesEventsTest extends TestCase
{
    use DatabaseTransactions;

    private function capturingCommand(): object
    {
        return new class extends \App\Console\Commands\UpdateMatchesEvents {
            public array $captured = [];

            public function topicNotifyByFirebaseTokens(array|Collection $tokens, array $data = []): void
            {
                $this->captured[] = $data;
            }
        };
    }

    private function fixtureAndTeam(): array
    {
        $matchModel = Matche::with(['team1.translations', 'team2.translations'])
            ->whereHas('team1.translations')
            ->whereHas('team2.translations')
            ->first();

        if (!$matchModel) {
            $this->markTestSkipped('No fully translated match available in DB.');
        }

        $team1 = $matchModel->team1;

        $unique   = uniqid('', true);
        $clientId = DB::table('clients')->insertGetId([
            'full_name'  => 'Events EN',
            'email'      => $unique . '@example.test',
            'api_token'  => 'api_' . $unique,
            'fb_token'   => 'fcm_' . $unique,
            'locale'     => 'en',
            'timezone'   => 'Africa/Cairo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('favourite_team')->insert(['team_id' => $team1->id, 'client_id' => $clientId]);

        $match = new Matche();
        $match->id = 990001;
        $match->fixture_id = 880001;
        $match->team1_id = $team1->id;
        $match->team2_id = $matchModel->team2_id;
        $match->home_team_name_en = 'Home EN';
        $match->home_team_name_ar = 'Home AR';
        $match->away_team_name_en = 'Away EN';
        $match->away_team_name_ar = 'Away AR';
        $match->home_goals = 2;
        $match->away_goals = 1;

        return [$match, $team1];
    }

    private function invokeSender($command, $matches, array $new): void
    {
        $method = new \ReflectionMethod($command, 'sendEventNotification');
        $method->setAccessible(true);
        $method->invoke($command, $matches, $new);
    }

    public function test_goal_notification_includes_score_and_added_time(): void
    {
        [$match, $team1] = $this->fixtureAndTeam();
        $command = $this->capturingCommand();

        $this->invokeSender($command, collect([$match]), [
            'event'        => ['time' => ['elapsed' => 45, 'extra' => 2]],
            'type'         => 'goal',
            'rawDetail'    => 'normal goal',
            'playerNameEn' => 'John Doe',
            'playerNameAr' => 'جون دو',
            'teamId'       => $team1->id,
            'matchEventId' => null,
        ]);

        $this->assertCount(1, $command->captured);
        $body = $command->captured[0]['body'];
        $this->assertStringContainsString('2-1', $body);
        $this->assertStringContainsString('45 + 2', $body);
        $this->assertSame('Home EN-Away EN', $command->captured[0]['title']);
    }

    public function test_goal_notification_without_added_time_has_no_plus_prefix(): void
    {
        [$match, $team1] = $this->fixtureAndTeam();

        $command = $this->capturingCommand();

        $this->invokeSender($command, collect([$match]), [
            'event'        => ['time' => ['elapsed' => 30, 'extra' => null]],
            'type'         => 'goal',
            'rawDetail'    => 'normal goal',
            'playerNameEn' => 'John Doe',
            'playerNameAr' => 'جون دو',
            'teamId'       => $team1->id,
            'matchEventId' => null,
        ]);

        $this->assertCount(1, $command->captured);
        $this->assertStringNotContainsString(' + ', $command->captured[0]['body']);
    }
}
