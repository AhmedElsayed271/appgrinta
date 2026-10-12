<?php

namespace Tests\Feature;

use App\Models\Competition;
use App\Models\Matche;
use App\Models\Post;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationViewPath;
use Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect;
use Tests\TestCase;

/**
 * Coverage for the dashboard "manual broadcast" buttons: post / url / team /
 * competition notifications. Their shared payload flattening is already tested;
 * here we assert each endpoint persists an in-app notification of the right
 * type for both locales.
 */
class DashboardNotificationSendersTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        // The localization middleware redirects a bare POST before it reaches
        // the controller; the senders themselves are what we test here.
        $this->withoutMiddleware([
            LocaleSessionRedirect::class,
            LaravelLocalizationRedirectFilter::class,
            LaravelLocalizationViewPath::class,
        ]);
    }

    private function client(string $locale): int
    {
        $unique = uniqid('', true);

        return DB::table('clients')->insertGetId([
            'full_name'  => 'Dash ' . $locale,
            'email'      => $unique . '@example.test',
            'api_token'  => 'dash_' . $unique,
            'fb_token'   => 'fcm_dash_' . $unique,
            'locale'     => $locale,
            'timezone'   => 'Africa/Cairo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function admin(): User
    {
        $unique = uniqid('', true);

        $id = DB::table('users')->insertGetId([
            'first_name' => 'Admin',
            'last_name'  => 'Tester',
            'email'      => $unique . '@example.test',
            'phone'      => '0100000000',
            'password'   => bcrypt('secret'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return User::findOrFail($id);
    }

    private function translatedTeam(): Team
    {
        $match = Matche::query()
            ->whereHas('team1.translations', fn ($q) => $q->where('locale', 'ar'))
            ->whereHas('team1.translations', fn ($q) => $q->where('locale', 'en'))
            ->first();

        if (!$match) {
            $this->markTestSkipped('No translated team available.');
        }

        return Team::findOrFail($match->team1_id);
    }

    private function translatedCompetition(): Competition
    {
        $competition = Competition::query()
            ->whereHas('translations', fn ($q) => $q->where('locale', 'ar'))
            ->whereHas('translations', fn ($q) => $q->where('locale', 'en'))
            ->first();

        if (!$competition) {
            $this->markTestSkipped('No translated competition available.');
        }

        return $competition;
    }

    public function test_url_post_persists_announcement_for_both_locales(): void
    {
        Bus::fake();
        $en = $this->client('en');
        $ar = $this->client('ar');

        $this->actingAs($this->admin())->post('/en/dashboard/notifications/url_post', [
            'ar' => ['name' => 'عنوان', 'description' => 'وصف'],
            'en' => ['name' => 'Title', 'description' => 'Body'],
            'url' => 'https://example.test/x',
        ])->assertRedirect();

        foreach ([$en, $ar] as $clientId) {
            $row = DB::table('notifications')->where('client_id', $clientId)->where('type', 'announcement')->first();
            $this->assertNotNull($row, 'announcement notification must be stored for client ' . $clientId);
            $this->assertSame('https://example.test/x', json_decode($row->data, true)['url'] ?? null);
        }
    }

    public function test_team_post_persists_team_notification(): void
    {
        Bus::fake();
        $en    = $this->client('en');
        $team  = $this->translatedTeam();

        $this->actingAs($this->admin())->post('/en/dashboard/notifications/team_post', [
            'team_id' => $team->id,
            'screen'  => '2',
            'ar'      => ['name' => 'عنوان', 'description' => 'وصف'],
            'en'      => ['name' => 'Title', 'description' => 'Body'],
        ])->assertRedirect();

        $row = DB::table('notifications')->where('client_id', $en)->where('type', 'team')->first();
        $this->assertNotNull($row, 'team notification must be stored');
        $data = json_decode($row->data, true);
        $this->assertSame('team', $data['type']);
        $this->assertSame('2', $data['screen']);
    }

    public function test_competition_post_persists_league_notification(): void
    {
        Bus::fake();
        $en = $this->client('en');
        $competition = $this->translatedCompetition();

        $this->actingAs($this->admin())->post('/en/dashboard/notifications/competition_post', [
            'competition_id' => $competition->id,
            'screen'         => '1',
            'ar'             => ['name' => 'دوري', 'description' => 'وصف'],
            'en'             => ['name' => 'League', 'description' => 'Body'],
        ])->assertRedirect();

        $row = DB::table('notifications')->where('client_id', $en)->where('type', 'league')->first();
        $this->assertNotNull($row, 'league notification must be stored');
        $this->assertSame('league', json_decode($row->data, true)['type']);
    }

    public function test_post_post_persists_post_notification(): void
    {
        Bus::fake();
        $en = $this->client('en');

        $post = Post::query()->first();
        if (!$post) {
            $this->markTestSkipped('No post available.');
        }

        $this->actingAs($this->admin())->post('/en/dashboard/notifications/post_post', [
            'post_id' => $post->id,
            'ar'      => ['name' => 'خبر', 'description' => 'وصف'],
            'en'      => ['name' => 'Post', 'description' => 'Body'],
        ])->assertRedirect();

        $row = DB::table('notifications')->where('client_id', $en)->where('type', 'post')->first();
        $this->assertNotNull($row, 'post notification must be stored');
        $this->assertSame((string) $post->id, (string) json_decode($row->data, true)['post_id']);
    }
}
