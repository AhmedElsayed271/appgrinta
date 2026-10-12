<?php

namespace Tests\Feature;

use App\Services\FirebaseService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Regression guards for the hardening fixes:
 *  - Firebase access-token retrieval never caches a failed/empty token.
 *  - Sending with an empty token list must not touch Firebase at all.
 *  - `events:update` must not fatal when the `testtest` control row is missing.
 *  - The public daily/weekly notification endpoints stay rate limited.
 */
class RobustnessTest extends TestCase
{
    use DatabaseTransactions;

    public function test_firebase_access_token_is_reused_from_cache(): void
    {
        cache()->put('firebase_access_token', 'cached-token', 60);

        $service = new FirebaseService();

        $this->assertSame('cached-token', $service->getAccessToken());
    }

    public function test_firebase_failed_token_fetch_is_not_cached(): void
    {
        cache()->forget('firebase_access_token');

        // Incomplete service account config: token exchange must fail before
        // any network call and must never be cached.
        config(['firebase.credentials' => ['type' => 'service_account']]);

        $service = new FirebaseService();

        try {
            $service->getAccessToken();
        } catch (\Throwable $e) {
            // expected
        }

        $this->assertFalse(
            cache()->has('firebase_access_token'),
            'a failed token fetch must not be cached'
        );
    }

    public function test_firebase_empty_tokens_skip_token_fetch(): void
    {
        cache()->forget('firebase_access_token');
        config(['firebase.credentials' => ['type' => 'service_account']]);

        $service = new FirebaseService();

        // Would throw if it tried to reach Google for an access token.
        $service->sendNotification([], ['title' => 'x', 'body' => 'y']);

        $this->assertFalse(cache()->has('firebase_access_token'));
    }

    public function test_events_update_handles_missing_testtest_row(): void
    {
        Bus::fake();

        DB::table('testtest')->where('id', 1)->delete();

        $exit = Artisan::call('events:update');

        $this->assertSame(0, $exit);
    }

    public function test_daily_and_weekly_routes_are_rate_limited(): void
    {
        $routes = app('router')->getRoutes();

        foreach (['api/v1/notification/daily/{id}', 'api/v1/notification/weekly/{id}'] as $uri) {
            $route = collect($routes)->first(fn ($r) => $r->uri() === $uri);

            $this->assertNotNull($route, "route {$uri} is missing");
            $this->assertContains('throttle:60,1', $route->gatherMiddleware(), "route {$uri} is not rate limited");
        }
    }
}
