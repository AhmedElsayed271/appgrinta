<?php

namespace Tests\Feature;

use App\Models\Client;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * `timezone` is accepted on both login and register.
 * - login: optional. When sent, it overwrites the stored value; when missing
 *   the client keeps what it already has.
 * - register: optional. Missing means the column default stays untouched.
 */
class TimezoneParamTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        // The login route is throttled; every test starts with a clean bucket.
        Cache::flush();
    }

    public function test_login_without_timezone_keeps_the_stored_value(): void
    {
        $client = $this->makeClient('Africa/Cairo');

        $this->login($client)->assertStatus(200);

        $this->assertSame('Africa/Cairo', $client->fresh()->timezone);
    }

    public function test_login_saves_the_timezone_when_the_client_sends_it(): void
    {
        $client = $this->makeClient('Africa/Cairo');

        $this->login($client, ['timezone' => 'Asia/Riyadh'])->assertStatus(200);

        $this->assertSame('Asia/Riyadh', $client->fresh()->timezone);
    }

    public function test_login_rejects_an_unknown_timezone(): void
    {
        $client = $this->makeClient('Africa/Cairo');

        $this->login($client, ['timezone' => 'Not/AZone'])->assertStatus(422);

        $this->assertSame('Africa/Cairo', $client->fresh()->timezone);
    }

    public function test_register_saves_the_timezone_when_sent(): void
    {
        $email = 'tz-' . uniqid() . '@example.com';

        $this->register($email, ['timezone' => 'Europe/London'])->assertStatus(200);

        $this->assertSame(
            'Europe/London',
            Client::query()->where('email', $email)->value('timezone')
        );
    }

    public function test_register_without_timezone_uses_the_column_default(): void
    {
        $email = 'tz-' . uniqid() . '@example.com';

        $this->register($email)->assertStatus(200);

        $this->assertNotNull(
            Client::query()->where('email', $email)->value('timezone')
        );
    }

    public function test_social_login_saves_the_timezone_for_an_existing_account(): void
    {
        $client = Client::query()->create([
            'full_name'  => 'Social TZ',
            'email'      => 'tz-' . uniqid() . '@example.com',
            'password'   => Hash::make('secret123'),
            'social_id'  => 'fb-tz-' . uniqid(),
            'timezone'   => 'Africa/Cairo',
            'locale'     => 'en',
        ]);

        $this->social($client->social_id, ['timezone' => 'Asia/Riyadh'])->assertStatus(200);

        $this->assertSame('Asia/Riyadh', $client->fresh()->timezone);
    }

    public function test_social_login_without_timezone_keeps_the_stored_value(): void
    {
        $client = Client::query()->create([
            'full_name'  => 'Social TZ',
            'email'      => 'tz-' . uniqid() . '@example.com',
            'password'   => Hash::make('secret123'),
            'social_id'  => 'fb-tz-' . uniqid(),
            'timezone'   => 'Europe/London',
            'locale'     => 'en',
        ]);

        $this->social($client->social_id)->assertStatus(200);

        $this->assertSame('Europe/London', $client->fresh()->timezone);
    }

    public function test_social_registration_saves_the_timezone(): void
    {
        $socialId = 'fb-tz-' . uniqid();

        $this->social($socialId, [
            'email'    => 'tz-' . uniqid() . '@example.com',
            'timezone' => 'America/New_York',
        ])->assertStatus(200);

        $this->assertSame(
            'America/New_York',
            Client::query()->where('social_id', $socialId)->value('timezone')
        );
    }

    public function test_social_login_rejects_an_unknown_timezone(): void
    {
        $this->social('fb-tz-' . uniqid(), [
            'email'    => 'tz-' . uniqid() . '@example.com',
            'timezone' => 'GMT+2',
        ])->assertStatus(422);
    }

    private function makeClient(string $timezone): Client
    {
        return Client::query()->create([
            'full_name'  => 'Timezone Tester',
            'email'      => 'tz-' . uniqid() . '@example.com',
            'password'   => Hash::make('secret123'),
            'locale'     => 'en',
            'timezone'   => $timezone,
            'fb_token'   => 'test-fb-token',
        ]);
    }

    private function login(Client $client, array $extra = [])
    {
        return $this->postJson('/api/v1/login', array_merge([
            'email'    => $client->email,
            'password' => 'secret123',
            'fb_token' => 'test-fb-token',
            'locale'   => 'en',
        ], $extra));
    }

    private function register(string $email, array $extra = [])
    {
        return $this->postJson('/api/v1/register', array_merge([
            'full_name'             => 'Timezone Tester',
            'email'                 => $email,
            'password'              => 'secret123',
            'password_confirmation' => 'secret123',
            'fb_token'              => 'test-fb-token',
            'locale'                => 'en',
        ], $extra));
    }

    private function social(string $socialId, array $extra = [])
    {
        return $this->postJson('/api/v1/social', array_merge([
            'provider'  => 'facebook',
            'token'     => 'test-social-token',
            'social_id' => $socialId,
            'fb_token'  => 'test-fb-token',
            'locale'    => 'en',
        ], $extra));
    }
}
