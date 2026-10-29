<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Every 429 (rate limit) must be written to the dedicated "rate_limit" log
 * channel with enough request context to find the offending client/endpoint.
 */
class RateLimitLoggingTest extends TestCase
{
    private string $logPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logPath = storage_path('logs/rate-limit-test.log');

        if (file_exists($this->logPath)) {
            @unlink($this->logPath);
        }

        // Write the channel to a throw-away file while the test runs.
        config(['logging.channels.rate_limit' => [
            'driver' => 'single',
            'path' => $this->logPath,
            'level' => 'debug',
        ]]);

        // Rate limiter counters live in the cache store; start clean.
        Cache::flush();
    }

    protected function tearDown(): void
    {
        if (file_exists($this->logPath)) {
            @unlink($this->logPath);
        }

        Cache::flush();

        parent::tearDown();
    }

    public function test_rate_limited_request_is_logged_with_request_context(): void
    {
        Route::middleware('throttle:1,1')->get('/__test/rate-limited', function () {
            return response()->json(['ok' => true]);
        })->name('__test.rate_limited');

        $this->get('/__test/rate-limited')->assertOk();

        $this->get('/__test/rate-limited')
            ->assertStatus(429)
            ->assertJsonPath('code', 429)
            ->assertJsonPath('error', 'Too Many Attempts.');

        $this->assertFileExists($this->logPath, 'The rate limit log file was not written.');

        $log = file_get_contents($this->logPath);

        $this->assertStringContainsString('Rate limit exceeded (429)', $log);
        $this->assertStringContainsString('/__test/rate-limited', $log);
        $this->assertStringContainsString('127.0.0.1', $log);
        $this->assertStringContainsString('"route":"__test.rate_limited"', $log);
    }

    public function test_successful_requests_are_not_logged(): void
    {
        Route::middleware('throttle:60,1')->get('/__test/not-limited', function () {
            return response()->json(['ok' => true]);
        });

        $this->get('/__test/not-limited')->assertOk();

        $this->assertFileDoesNotExist($this->logPath, 'Nothing should be logged for successful requests.');
    }
}
