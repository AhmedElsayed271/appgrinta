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
}
