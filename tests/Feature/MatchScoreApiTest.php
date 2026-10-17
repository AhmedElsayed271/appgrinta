<?php

namespace Tests\Feature;

use App\Models\Matche;
use App\Models\MatchEvent;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MatchScoreApiTest extends TestCase
{
    use DatabaseTransactions;

    private function client(string $locale = 'en'): array
    {
        $suffix = uniqid('', true);

        $id = DB::table('clients')->insertGetId([
            'full_name'  => 'Score ' . $locale,
            'email'      => "score_{$locale}_{$suffix}@example.test",
            'api_token'  => "tok_score_{$suffix}",
            'fb_token'   => "fcm_score_{$suffix}",
            'locale'     => $locale,
            'timezone'   => 'Africa/Cairo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$id, DB::table('clients')->where('id', $id)->value('api_token')];
    }

    private function goal(int $matchId, int $teamId, string $status, int $minute): void
    {
        MatchEvent::create([
            'minute'     => $minute,
            'status'     => $status,
            'player_id'  => null,
            'team_id'    => $teamId,
            'match_id'   => $matchId,
            'event_type' => 'AUTO',
        ]);
    }

    public function test_match_endpoint_counts_goals_from_all_stored_formats(): void
    {
        $match = Matche::query()->whereNotNull('team1_id')->whereNotNull('team2_id')->first();
        if (!$match) {
            $this->markTestSkipped('No matches in database.');
        }

        $match->events()->delete();

        // team1: normal + penalty goal; team2: normal goal
        $this->goal($match->id, $match->team1_id, 'goal-normal goal', 10);
        $this->goal($match->id, $match->team1_id, 'goal-penalty', 20);
        $this->goal($match->id, $match->team2_id, 'goal-normal goal', 30);

        // own goal by team2 → credited to team1
        $this->goal($match->id, $match->team2_id, 'goal-own goal', 40);

        // must NOT count
        $this->goal($match->id, $match->team1_id, 'goal-missed penalty', 50);
        $this->goal($match->id, $match->team2_id, 'var-goal cancelled', 60);

        [$_, $token] = $this->client('en');

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->getJson('/api/v1/matches/' . $match->id);

        $response->assertOk()
            ->assertJsonPath('data.home_goals', 3)
            ->assertJsonPath('data.away_goals', 1);
    }

    public function test_legacy_goal_status_is_still_counted(): void
    {
        $match = Matche::query()->first();
        if (!$match) {
            $this->markTestSkipped('No matches in database.');
        }

        $match->events()->delete();

        $this->goal($match->id, $match->team1_id, 'goal', 12);
        $this->goal($match->id, $match->team2_id, 'goal-normal goal', 33);

        [$_, $token] = $this->client('en');

        $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->getJson('/api/v1/matches/' . $match->id)
            ->assertOk()
            ->assertJsonPath('data.home_goals', 1)
            ->assertJsonPath('data.away_goals', 1);
    }
}
