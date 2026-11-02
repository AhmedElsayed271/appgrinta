<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * `POST /api/v1/login` is limited to 10 attempts per minute per IP. Once the
 * bucket is empty the API must answer 429 with the throttle headers AND the
 * remaining seconds inside the JSON body so the mobile app can run a countdown.
 */
class LoginRateLimitTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Throttle counters live in the cache store; every test starts clean.
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Cache::flush();

        parent::tearDown();
    }

    public function test_login_is_limited_to_ten_attempts_per_minute(): void
    {
        for ($attempt = 1; $attempt <= 10; $attempt++) {
            $response = $this->login();

            $this->assertNotSame(
                429,
                $response->getStatusCode(),
                "Attempt {$attempt} must still reach the controller."
            );
        }

        $blocked = $this->login();

        $blocked->assertStatus(429)
            ->assertJsonPath('isSuccess', false)
            ->assertJsonPath('code', 429)
            ->assertJsonPath('limit', 10)
            ->assertJsonPath('remaining', 0);

        $retryAfter = (int) $blocked->json('retry_after');

        $this->assertGreaterThan(0, $retryAfter);
        $this->assertLessThanOrEqual(60, $retryAfter);
        $this->assertSame($retryAfter, (int) $blocked->json('seconds_left'));
        $this->assertSame((string) $retryAfter, $blocked->headers->get('Retry-After'));
        $this->assertSame('10', $blocked->headers->get('X-RateLimit-Limit'));
        $this->assertStringContainsString('Too many login attempts', $blocked->json('error'));
    }

    public function test_the_bucket_is_per_ip_address(): void
    {
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $this->login();
        }

        $this->login()->assertStatus(429);

        // Another client (different IP) is not affected by the blocked one.
        $this->withServerVariables(['REMOTE_ADDR' => '10.10.10.10']);

        $this->login()->assertStatus(400);
    }

    public function test_the_message_follows_the_requested_locale(): void
    {
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $this->login('ar');
        }

        $blocked = $this->login('ar');

        $blocked->assertStatus(429);
        $this->assertStringContainsString('محاولات الدخول', $blocked->json('error'));
    }

    private function login(string $locale = 'en')
    {
        return $this->postJson('/api/v1/login', [
            'email'    => 'nobody@example.com',
            'password' => 'secret123',
            'fb_token' => 'test-token',
            'locale'   => $locale,
        ]);
    }
}
