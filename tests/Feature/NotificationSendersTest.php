<?php

namespace Tests\Feature;

use App\Models\Matche;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Covers the remaining automatic broadcast senders and the shared payload
 * flattening used by the dashboard manual senders.
 */
class NotificationSendersTest extends TestCase
{
    use DatabaseTransactions;

    private function client(string $locale, string $timezone = 'Africa/Cairo', ?string $fbToken = null): int
    {
        $unique = uniqid('', true);

        return DB::table('clients')->insertGetId([
            'full_name'  => 'Sender ' . $locale,
            'email'      => $unique . '@example.test',
            'api_token'  => 'api_' . $unique,
            'fb_token'   => $fbToken ?? ('fcm_' . $unique),
            'locale'     => $locale,
            'timezone'   => $timezone,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_static_command_persists_bilingual_reminder_for_clients(): void
    {
        Bus::fake();

        $en = $this->client('en');
        $ar = $this->client('ar');

        Artisan::call('notification:static');

        foreach ([$en, $ar] as $clientId) {
            $row = DB::table('notifications')->where('client_id', $clientId)->where('type', 'reminder')->first();
            $this->assertNotNull($row, 'static notification must be persisted for client ' . $clientId);

            $data = json_decode($row->data, true);
            $this->assertSame('reminder', $data['type']);
            $this->assertSame('matches', $data['url']);
        }
    }

    public function test_favourite_team_command_persists_team_type_notification(): void
    {
        Bus::fake();

        $match = Matche::with(['team1.translations', 'team2.translations'])
            ->whereHas('team1.translations')
            ->whereHas('team2.translations')
            ->first();

        if (!$match) {
            $this->markTestSkipped('No fully translated match available in DB.');
        }

        $date = '2026-01-01';
        $match->match_date = $date . ' 12:00:00';
        $match->save();

        $clientId = $this->client('en', 'Africa/Cairo');
        DB::table('favourite_team')->insert(['team_id' => $match->team1_id, 'client_id' => $clientId]);

        Artisan::call('notification:favourite_team', ['--date' => $date]);

        $row = DB::table('notifications')->where('client_id', $clientId)->where('type', 'team')->first();
        $this->assertNotNull($row, 'favourite-team notification must be persisted');

        $data = json_decode($row->data, true);
        $this->assertSame('team', $data['type']);
        $this->assertSame('1', $data['screen'], 'team notifications must expose screen="1"');
        $this->assertSame((string) $match->id, $data['match_id']);
    }

    public function test_flatten_notify_stringifies_scalars_and_nested_arrays(): void
    {
        $controller = new \App\Http\Controllers\Dashboard\NotificationsController();

        $method = new \ReflectionMethod($controller, 'flattenNotify');
        $method->setAccessible(true);

        $flat = $method->invoke($controller, [
            'team_id' => 123,
            'screen'  => '1',
            'team'    => ['x' => 1],
        ]);

        $this->assertSame('123', $flat['team_id'], 'integers become strings');
        $this->assertSame('1', $flat['screen']);
        $this->assertSame('{"x":1}', $flat['team'], 'nested arrays are JSON-encoded');
    }

    public function test_dashboard_match_post_route_matches_controller_signature(): void
    {
        $route = \Illuminate\Support\Facades\Route::getRoutes()->getByName('dashboard.notifications.match_post');
        $this->assertNotNull($route, 'dashboard.notifications.match_post route must exist');
        $this->assertNotContains('match_id', $route->parameterNames(), 'match_id must come from the form, not the URL');

        $method = new \ReflectionMethod(
            \App\Http\Controllers\Dashboard\NotificationsController::class,
            'match_post'
        );

        $this->assertSame(
            1,
            $method->getNumberOfRequiredParameters(),
            'match_post must require only the Request (match_id comes from the form input)'
        );
    }

    public function test_daily_and_weekly_endpoints_return_stored_fixtures(): void
    {
        $league = 987654;

        DB::table('daily_matches')->insert([
            'league_id' => $league,
            'response'  => json_encode([['fixture' => ['id' => 11]]]),
        ]);
        DB::table('weekly_matches')->insert([
            'league_id' => $league,
            'response'  => json_encode([['fixture' => ['id' => 22]]]),
        ]);

        $this->getJson('/api/v1/notification/daily/' . $league)
            ->assertOk()
            ->assertJsonPath('isSuccess', true)
            ->assertJsonPath('data.0.fixture.id', 11);

        $this->getJson('/api/v1/notification/weekly/' . $league)
            ->assertOk()
            ->assertJsonPath('data.0.fixture.id', 22);
    }

    public function test_daily_endpoint_returns_empty_data_for_unknown_league(): void
    {
        $this->getJson('/api/v1/notification/daily/111111')
            ->assertOk()
            ->assertJsonPath('data', []);
    }
}
