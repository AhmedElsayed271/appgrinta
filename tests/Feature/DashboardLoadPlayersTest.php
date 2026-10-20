<?php

namespace Tests\Feature;

use App\Http\Middleware\Authenticate;
use App\Http\Middleware\RoleMiddleware;
use App\Models\Player;
use App\Models\Team;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DashboardLoadPlayersTest extends TestCase
{
    use DatabaseTransactions;

    private function team(): Team
    {
        $team = Team::query()->whereNotNull('team_id')->first();
        if (!$team) {
            $this->markTestSkipped('No team with a football api id available in DB.');
        }
        return $team;
    }

    public function test_load_players_route_binds_team_and_returns_ok(): void
    {
        $team = $this->team();

        Http::fake([
            '*' => Http::response(['paging' => ['total' => 1], 'response' => []], 200),
        ]);

        $this->withoutMiddleware([Authenticate::class, RoleMiddleware::class]);

        $this->get("/en/dashboard/teams/{$team->id}/loadPlayers")
            ->assertOk()
            ->assertSee('players loaded', false);
    }

    public function test_load_players_reports_loaded_count(): void
    {
        $team = $this->team();

        $payload = [
            'paging' => ['total' => 1, 'current' => 1],
            'response' => [[
                'player' => [
                    'id' => 990001,
                    'firstname' => 'Test',
                    'lastname' => 'Player',
                    'photo' => 'http://example.test/player.png',
                ],
                'statistics' => [[
                    'team' => [
                        'id' => (int) $team->team_id,
                        'name' => 'API Team',
                        'logo' => 'http://example.com/t.png',
                    ],
                    'games' => ['position' => 'Midfielder'],
                ]],
            ]],
        ];

        Http::fake(['*' => Http::response($payload, 200)]);

        $this->withoutMiddleware([
            Authenticate::class,
            RoleMiddleware::class,
        ]);

        $this->get("/en/dashboard/teams/{$team->id}/loadPlayers")
            ->assertOk()
            ->assertSee('players loaded: 1 | new: 1', false);

        $this->assertDatabaseHas('players', [
            'player_id' => 990001,
            'football_team_id' => (int) $team->team_id,
        ]);

        $this->get("/en/dashboard/teams/{$team->id}/loadPlayers")
            ->assertOk()
            ->assertSee('players loaded: 1 | new: 0', false);
    }
}
