<?php

namespace Tests\Feature;

use App\Models\Client;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Accounts created by older app versions have no email. The email is only
 * available at login time, so the social endpoints must store it when the
 * account is found by its social_id and the email is still empty.
 */
class SocialEmailBackfillTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_google_login_fills_the_missing_email(): void
    {
        $client = $this->socialClient('google-sub-1');

        $this->fakeGoogle(['sub' => 'google-sub-1', 'email' => 'backfill@example.com', 'name' => 'Backfill User']);

        $this->postJson('/api/v1/social', $this->socialPayload())
            ->assertStatus(200)
            ->assertJsonPath('isSuccess', true);

        $client->refresh();

        $this->assertSame('backfill@example.com', $client->email);
        $this->assertNotNull($client->email_verified_at);
    }

    public function test_existing_email_is_never_overwritten(): void
    {
        $client = $this->socialClient('google-sub-2', 'keep@example.com');

        $this->fakeGoogle(['sub' => 'google-sub-2', 'email' => 'different@example.com', 'name' => 'Keep Me']);

        $this->postJson('/api/v1/social', $this->socialPayload())->assertStatus(200);

        $this->assertSame('keep@example.com', $client->refresh()->email);
    }

    public function test_email_owned_by_another_client_is_not_taken_over(): void
    {
        $owner = Client::query()->create([
            'full_name' => 'Owner',
            'email'     => 'taken@example.com',
            'password'  => bcrypt('secret123'),
            'locale'    => 'en',
        ]);

        $client = $this->socialClient('google-sub-3');

        $this->fakeGoogle(['sub' => 'google-sub-3', 'email' => 'taken@example.com', 'name' => 'Duplicate']);

        // Login must still succeed, but the email stays with its current owner.
        $this->postJson('/api/v1/social', $this->socialPayload())->assertStatus(200);

        $this->assertNull($client->refresh()->email);
        $this->assertSame('taken@example.com', $owner->refresh()->email);
    }

    public function test_unverified_provider_email_is_saved_but_not_marked_verified(): void
    {
        $client = $this->socialClient('fb-1');

        $this->postJson('/api/v1/social', [
            'provider'  => 'facebook',
            'token'     => 'fb-access-token',
            'social_id' => 'fb-1',
            'email'     => 'facebook@example.com',
            'fb_token'  => 'device-token',
            'locale'    => 'en',
        ])->assertStatus(200);

        $client->refresh();

        $this->assertSame('facebook@example.com', $client->email);
        $this->assertNull($client->email_verified_at);
    }

    private function socialClient(string $socialId, ?string $email = null): Client
    {
        return Client::query()->create([
            'full_name' => 'Social User',
            'email'     => $email,
            'password'  => bcrypt('secret123'),
            'social_id' => $socialId,
            'locale'    => 'en',
        ]);
    }

    private function socialPayload(): array
    {
        return [
            'provider' => 'google',
            'token'    => 'fake-token',
            'fb_token' => 'device-token',
            'locale'   => 'en',
        ];
    }

    private function fakeGoogle(array $profile): void
    {
        Http::fake([
            'https://www.googleapis.com/oauth2/v3/userinfo' => Http::response($profile, 200),
        ]);
    }
}
