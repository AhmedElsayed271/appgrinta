<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Write side of the stored daily/weekly match feeds: the commands that fetch
 * from API-Sports and upsert into `daily_matches` / `weekly_matches`, which are
 * then served by the public notification/daily|weekly endpoints.
 */
class StoredMatchesCommandsTest extends TestCase
{
    use DatabaseTransactions;

    public function test_daily_command_stores_fixtures_and_endpoint_serves_them(): void
    {
        $league = 777001;

        Http::fake([
            '*' => Http::response(['response' => [['fixture' => ['id' => 555]]]], 200),
        ]);

        $exit = Artisan::call('notification:daily', ['league' => (string) $league]);
        $this->assertSame(0, $exit);

        $row = DB::table('daily_matches')->where('league_id', $league)->first();
        $this->assertNotNull($row, 'the daily command must store a row');
        $this->assertSame(555, json_decode($row->response, true)[0]['fixture']['id']);

        $this->getJson('/api/v1/notification/daily/' . $league)
            ->assertOk()
            ->assertJsonPath('data.0.fixture.id', 555);
    }

    public function test_weekly_command_stores_fixtures_and_endpoint_serves_them(): void
    {
        $league = 777002;

        Http::fake([
            '*' => Http::response(['response' => [['fixture' => ['id' => 888]]]], 200),
        ]);

        $exit = Artisan::call('notification:weekly', ['league' => (string) $league]);
        $this->assertSame(0, $exit);

        $row = DB::table('weekly_matches')->where('league_id', $league)->first();
        $this->assertNotNull($row, 'the weekly command must store a row');
        $this->assertSame(888, json_decode($row->response, true)[0]['fixture']['id']);

        $this->getJson('/api/v1/notification/weekly/' . $league)
            ->assertOk()
            ->assertJsonPath('data.0.fixture.id', 888);
    }

    public function test_daily_command_does_not_store_on_api_error(): void
    {
        $league = 777003;

        Http::fake([
            '*' => Http::response(['errors' => ['token' => 'invalid key']], 200),
        ]);

        $exit = Artisan::call('notification:daily', ['league' => (string) $league]);

        $this->assertSame(1, $exit, 'a failed API response must return a non-zero exit code');
        $this->assertNull(
            DB::table('daily_matches')->where('league_id', $league)->first(),
            'nothing must be stored when the API reports an error'
        );
    }
}
