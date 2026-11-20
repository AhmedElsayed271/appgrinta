<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Competition;
use App\Models\CompetitionTranslation;
use App\Models\Country;
use App\Models\DeepLink;
use App\Models\Matche;
use App\Models\Post;
use App\Models\PostTranslation;
use App\Models\Team;
use App\Models\TeamTranslation;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Self-hosted dynamic links: verification files, landing/share pages and the
 * public API used by the mobile app (including the iOS manual claim code).
 */
class DeepLinksTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    protected function tearDown(): void
    {
        Cache::flush();

        parent::tearDown();
    }

    public function test_android_assetlinks_contains_both_fingerprints(): void
    {
        $contents = file_get_contents(public_path('.well-known/assetlinks.json'));
        $this->assertNotFalse($contents);

        $data = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        $fingerprints = $data[0]['target']['sha256_cert_fingerprints'];

        $this->assertSame('com.muhmmad.grinta', $data[0]['target']['package_name']);
        $this->assertContains(
            '12:F0:F3:90:99:3C:D1:21:93:D1:1E:EF:C1:24:ED:99:8F:80:1B:01:82:35:A1:D1:E0:F5:15:C5:5D:9F:4A:BB',
            $fingerprints
        );
        $this->assertContains(
            '33:56:A2:08:44:59:F5:E1:33:4F:8F:0A:62:78:F6:4B:5D:17:40:19:B9:93:0E:D7:29:B1:D1:62:1D:8D:E1:D5',
            $fingerprints
        );
    }

    public function test_aasa_declares_short_links(): void
    {
        $contents = file_get_contents(public_path('.well-known/apple-app-site-association'));
        $this->assertNotFalse($contents);

        $data = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        $components = $data['applinks']['details'][0]['components'];
        $paths = array_column($components, '/');

        $this->assertContains('/l/*', $paths);
        $this->assertContains('/post/*', $paths);
        $this->assertContains('/league/*', $paths);
        $this->assertContains('/match/*', $paths);
        $this->assertContains('/team/*', $paths);
        $this->assertNotContains('/screen/*', $paths);
        $this->assertContains('6RPWXA5835.elsawy.Grinta', $data['applinks']['details'][0]['appIDs']);

        $htaccess = file_get_contents(public_path('.htaccess'));
        $this->assertStringContainsString('ForceType application/json', $htaccess);
    }

    public function test_it_creates_a_short_link_and_resolves_it_case_insensitively(): void
    {
        $post  = $this->createPost();
        $code  = $this->createLink('post', $post->id);

        $this->assertMatchesRegularExpression('/^GR-[A-Z0-9]{8}$/', $code);

        $created = $this->postJson('/api/v1/deeplinks', [
            'type' => 'post',
            'id'   => $post->id,
        ])->assertStatus(200)->json('data');

        // Same content must reuse the same code.
        $this->assertSame($code, $created['code']);

        $resolved = $this->getJson('/api/v1/deeplinks/resolve?code=' . strtolower($code))
            ->assertStatus(200)
            ->assertJsonPath('isSuccess', true)
            ->json('data');

        $this->assertSame($post->id, $resolved['post_id']);
        $this->assertSame('Test post', $resolved['title']);
        $this->assertArrayNotHasKey('target_type', $resolved);
        $this->assertArrayNotHasKey('target_id', $resolved);
        $this->assertArrayNotHasKey('screen', $resolved);
        $this->assertSame('/l/' . $code, parse_url($resolved['web_url'], PHP_URL_PATH));
        $this->assertStringNotContainsString('evil.example', json_encode($resolved));
    }

    public function test_it_resolves_a_league_and_records_a_click(): void
    {
        $league = $this->createLeague();
        $code   = $this->createLink('league', $league->id);

        $clicked = $this->postJson('/api/v1/deeplinks/click', ['code' => $code])
            ->assertStatus(200)
            ->json('data');

        $this->assertSame($league->id, $clicked['competition_id']);
        $this->assertSame('1', $clicked['screen']);
        $this->assertSame('Test league', $clicked['title']);
        $this->assertArrayNotHasKey('target_type', $clicked);
        $this->assertArrayNotHasKey('target_id', $clicked);

        $link = DeepLink::query()->where('code', $code)->firstOrFail();
        $this->assertSame(1, $link->clicks_count);
        $this->assertNotNull($link->last_clicked_at);
    }

    public function test_it_creates_and_resolves_a_match_link(): void
    {
        $match = $this->createMatch();
        $code  = $this->createLink('match', $match->id);

        $resolved = $this->getJson('/api/v1/deeplinks/resolve?code=' . $code)
            ->assertStatus(200)
            ->json('data');

        $this->assertSame($match->id, $resolved['match_id']);
        $this->assertSame('2', $resolved['screen']);
        $this->assertSame(778899, $resolved['fixture_id']);
        $this->assertSame($match->team1_id, $resolved['home_team_id']);
        $this->assertSame($match->team2_id, $resolved['away_team_id']);
        $this->assertSame($match->competition_id, $resolved['competition_id']);
        $this->assertSame('pending', $resolved['status']);
        $this->assertSame('Home FC vs Away FC', $resolved['title']);
        $this->assertSame('/match/' . $match->id, $resolved['path']);
        $this->assertArrayNotHasKey('target_type', $resolved);
        $this->assertArrayNotHasKey('target_id', $resolved);
        $this->assertArrayNotHasKey('post_id', $resolved);
        $this->assertArrayNotHasKey('team_id', $resolved);
    }

    public function test_it_creates_and_resolves_a_team_link_by_internal_or_external_id(): void
    {
        // A value above the internal id range keeps this deterministic on a
        // database that already contains real teams.
        $externalId = 2147483000;
        $team = $this->createTeam('Al Ahly', $externalId);
        $code = $this->createLink('team', $team->id);

        $resolved = $this->getJson('/api/v1/deeplinks/resolve?code=' . $code)
            ->assertStatus(200)
            ->json('data');

        $this->assertSame($externalId, $resolved['team_id']);
        $this->assertSame('1', $resolved['screen']);
        $this->assertSame('Al Ahly', $resolved['title']);
        $this->assertSame('/team/' . $team->id, $resolved['path']);
        $this->assertArrayNotHasKey('target_type', $resolved);
        $this->assertArrayNotHasKey('target_id', $resolved);
        $this->assertArrayNotHasKey('match_id', $resolved);
        $this->assertArrayNotHasKey('fixture_id', $resolved);

        // The external Football-API id must reuse the same code.
        $again = $this->postJson('/api/v1/deeplinks', ['type' => 'team', 'id' => $externalId])
            ->assertStatus(200)
            ->json('data');

        $this->assertSame($code, $again['code']);
    }

    public function test_screen_is_a_field_not_a_link_type(): void
    {
        $this->postJson('/api/v1/deeplinks', ['type' => 'screen', 'id' => 2])
            ->assertStatus(422);

        $this->get('/screen/2')->assertStatus(404);
    }

    public function test_the_ios_claim_endpoint_resolves_the_code_and_is_rate_limited(): void
    {
        $post = $this->createPost();
        $code = $this->createLink('post', $post->id);

        for ($attempt = 0; $attempt < 10; $attempt++) {
            $this->postJson('/api/v1/deeplinks/claim', ['code' => $code])
                ->assertStatus(200)
                ->assertJsonPath('data.code', $code);
        }

        $this->postJson('/api/v1/deeplinks/claim', ['code' => $code])->assertStatus(429);
    }

    public function test_scheduled_posts_cannot_be_shared(): void
    {
        $post = $this->createPost(true);

        $this->postJson('/api/v1/deeplinks', ['type' => 'post', 'id' => $post->id])
            ->assertStatus(404);
    }

    public function test_unknown_codes_are_not_found(): void
    {
        $this->getJson('/api/v1/deeplinks/resolve?code=GR-NOPE123')->assertStatus(404);
        $this->postJson('/api/v1/deeplinks/click', ['code' => 'GR-NOPE123'])->assertStatus(404);
        $this->get('/l/GR-NOPE123')->assertStatus(404);
    }

    public function test_landing_page_exposes_og_meta_claim_code_and_play_referrer(): void
    {
        $post = $this->createPost();
        $code = $this->createLink('post', $post->id);

        $response = $this->get('/l/' . $code . '?redirect=https://evil.example')->assertStatus(200);

        $response->assertSee('og:title', false)
            ->assertSee('og:image', false)
            ->assertSee('/l/' . $code, false)
            ->assertSee($code)
            ->assertSee('referrer=dl%3D' . $code, false)
            ->assertDontSee('evil.example');
    }

    public function test_a_temporary_play_url_is_not_shown_as_an_app_store_link(): void
    {
        config([
            'deeplinks.play_store_url' => 'https://play.google.com/store/apps/details?id=com.muhmmad.grinta',
            'deeplinks.app_store_url'  => 'https://play.google.com/store/apps/details?id=com.muhmmad.grinta',
        ]);

        $post = $this->createPost();
        $code = $this->createLink('post', $post->id);

        $this->get('/l/' . $code)
            ->assertStatus(200)
            ->assertSee('Google Play')
            ->assertDontSee('App Store');
    }

    public function test_a_real_app_store_url_restores_the_second_store_button(): void
    {
        config([
            'deeplinks.play_store_url' => 'https://play.google.com/store/apps/details?id=com.muhmmad.grinta',
            'deeplinks.app_store_url'  => 'https://apps.apple.com/app/grinta/id1234567890',
        ]);

        $post = $this->createPost();
        $code = $this->createLink('post', $post->id);

        $this->get('/l/' . $code)
            ->assertStatus(200)
            ->assertSee('Google Play')
            ->assertSee('App Store');
    }

    public function test_share_pages_render_without_a_localized_prefix(): void
    {
        $post   = $this->createPost();
        $league = $this->createLeague();
        $match  = $this->createMatch();
        $team   = $this->createTeam('Al Ahly', 4242);

        $this->get('/post/' . $post->id)
            ->assertStatus(200)
            ->assertSee('Test post')
            ->assertSee('/post/' . $post->id, false);

        $this->get('/league/' . $league->id)
            ->assertStatus(200)
            ->assertSee('Test league')
            ->assertSee('/league/' . $league->id, false);

        $this->get('/match/' . $match->id)
            ->assertStatus(200)
            ->assertSee('Home FC vs Away FC')
            ->assertSee('/match/' . $match->id, false);

        $this->get('/team/' . $team->id)
            ->assertStatus(200)
            ->assertSee('Al Ahly')
            ->assertSee('/team/' . $team->id, false);
    }

    private function createPost(bool $scheduled = false): Post
    {
        $category = Category::query()->create([]);

        $post = Post::query()->create([
            'category_id'  => $category->id,
            'image'        => 'post.png',
            'published_at' => $scheduled ? now()->addDay() : null,
        ]);

        $translation = new PostTranslation([
            'name'        => 'Test post',
            'description' => '<p>Test description</p>',
        ]);
        $translation->post_id = $post->id;
        $translation->locale  = 'en';
        $translation->save();

        return $post;
    }

    private function createLeague(): Competition
    {
        $country = Country::query()->create([]);

        $league = Competition::query()->create([
            'country_id' => $country->id,
            'image'      => 'league.png',
        ]);

        CompetitionTranslation::query()->create([
            'competition_id' => $league->id,
            'locale'         => 'en',
            'name'           => 'Test league',
        ]);

        return $league;
    }

    private function createTeam(string $name, int $externalId): Team
    {
        $team = Team::query()->create([
            'team_id' => $externalId,
            'image'   => 'team.png',
        ]);

        TeamTranslation::query()->create([
            'team_id' => $team->id,
            'locale'  => 'en',
            'name'    => $name,
        ]);

        return $team;
    }

    private function createMatch(): Matche
    {
        $home   = $this->createTeam('Home FC', 9001);
        $away   = $this->createTeam('Away FC', 9002);
        $league = $this->createLeague();

        return Matche::query()->create([
            'fixture_id'     => 778899,
            'team1_id'       => $home->id,
            'team2_id'       => $away->id,
            'competition_id' => $league->id,
            'match_date'     => now()->addDay(),
            'week'           => '1',
            'status'         => 'pending',
        ]);
    }

    private function createLink(string $type, int $id): string
    {
        return app(\App\Services\DeepLinkService::class)->create($type, $id)->code;
    }
}
