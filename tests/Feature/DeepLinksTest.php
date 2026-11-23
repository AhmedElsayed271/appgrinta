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
        $this->assertContains('/screen/*', $paths);
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

        $this->assertSame('post', $resolved['target_type']);
        $this->assertSame($post->id, $resolved['target_id']);
        $this->assertArrayNotHasKey('post_id', $resolved);
        $this->assertSame('Test post', $resolved['title']);
        // `screen` is a fixed key in every response (null for posts).
        $this->assertArrayHasKey('screen', $resolved);
        $this->assertNull($resolved['screen']);
        $this->assertSame('/l/' . $code, parse_url($resolved['web_url'], PHP_URL_PATH));
        $this->assertStringNotContainsString('evil.example', json_encode($resolved));
    }

    public function test_it_resolves_a_league_and_records_a_click(): void
    {
        $league = $this->createLeague();
        $code   = $this->createLink('league', $league->league_id, '1');

        $clicked = $this->postJson('/api/v1/deeplinks/click', ['code' => $code])
            ->assertStatus(200)
            ->json('data');

        $this->assertSame('league', $clicked['target_type']);
        $this->assertSame($league->id, $clicked['target_id']);
        $this->assertSame(2147483000, $clicked['league_id']);
        $this->assertArrayNotHasKey('competition_id', $clicked);
        $this->assertSame('1', $clicked['screen']);
        $this->assertSame('Test league', $clicked['title']);

        $link = DeepLink::query()->where('code', $code)->firstOrFail();
        $this->assertSame(1, $link->clicks_count);
        $this->assertNotNull($link->last_clicked_at);
    }

    public function test_it_creates_and_resolves_a_match_link(): void
    {
        $match = $this->createMatch();
        $code  = $this->createLink('match', $match->fixture_id, '2');

        $resolved = $this->getJson('/api/v1/deeplinks/resolve?code=' . $code)
            ->assertStatus(200)
            ->json('data');

        $this->assertSame('match', $resolved['target_type']);
        $this->assertSame($match->id, $resolved['target_id']);
        $this->assertArrayNotHasKey('match_id', $resolved);
        $this->assertSame('2', $resolved['screen']);
        $this->assertSame(778899, $resolved['fixture_id']);
        $this->assertSame($match->team1_id, $resolved['home_team_id']);
        $this->assertSame($match->team2_id, $resolved['away_team_id']);
        $this->assertSame($match->competition_id, $resolved['competition_id']);
        $this->assertSame('pending', $resolved['status']);
        $this->assertSame('Home FC vs Away FC', $resolved['title']);
        $this->assertSame('/match/' . $match->id, $resolved['path']);
        $this->assertArrayNotHasKey('post_id', $resolved);
        $this->assertArrayNotHasKey('team_id', $resolved);
    }

    public function test_create_accepts_only_football_api_id_for_match(): void
    {
        $match = $this->createMatch();

        $created = $this->postJson('/api/v1/deeplinks', [
            'type'   => 'match',
            'id'     => $match->fixture_id,
            'screen' => '2',
        ])->assertStatus(200)->json('data');

        $this->assertSame($match->id, $created['target_id']);
        $this->assertSame(778899, $created['fixture_id']);
        $this->assertArrayNotHasKey('match_id', $created);

        // Our internal id is not accepted by the create API for a match.
        $this->postJson('/api/v1/deeplinks', [
            'type' => 'match',
            'id' => $match->id,
            'screen' => '2',
        ])->assertStatus(404);
    }

    public function test_create_requires_id(): void
    {
        $this->postJson('/api/v1/deeplinks', ['type' => 'match', 'screen' => '2'])
            ->assertStatus(422);
    }

    public function test_league_accepts_external_id_in_id_field(): void
    {
        $league = $this->createLeague();

        $created = $this->postJson('/api/v1/deeplinks', [
            'type'   => 'league',
            'id'     => 2147483000,
            'screen' => '1',
        ])->assertStatus(200)->json('data');

        $this->assertSame($league->id, $created['target_id']);
        $this->assertSame(2147483000, $created['league_id']);
        $this->assertNotSame($created['target_id'], $created['league_id']);
        $this->assertArrayNotHasKey('competition_id', $created);

        // Our internal id is not accepted; pick one that is not also a
        // league_id on another competition (real data can contain both).
        $internalId = Competition::query()
            ->whereNotIn('id', Competition::query()->whereNotNull('league_id')->pluck('league_id'))
            ->value('id');

        $this->assertNotNull($internalId);

        $this->postJson('/api/v1/deeplinks', [
            'type'   => 'league',
            'id'     => $internalId,
            'screen' => '1',
        ])->assertStatus(404);

        $this->postJson('/api/v1/deeplinks', ['type' => 'league', 'screen' => '1'])
            ->assertStatus(422);
    }

    public function test_team_accepts_football_api_id(): void
    {
        $team = $this->createTeam('Al Ahly', 2147483001);

        $created = $this->postJson('/api/v1/deeplinks', [
            'type'   => 'team',
            'id'     => 2147483001,
            'screen' => '1',
        ])->assertStatus(200)->json('data');

        $this->assertSame($team->id, $created['target_id']);
        $this->assertSame(2147483001, $created['team_id']);

        // Our internal id is not accepted by the create API for a team. Pick
        // an internal id that is not also somebody's external team_id (real
        // data can contain both ranges, so the fixture id alone is not safe).
        $internalId = Team::query()
            ->whereNotIn('id', Team::query()->whereNotNull('team_id')->pluck('team_id'))
            ->value('id');

        $this->assertNotNull($internalId);

        $this->postJson('/api/v1/deeplinks', [
            'type' => 'team',
            'id' => $internalId,
            'screen' => '1',
        ])->assertStatus(404);
    }

    public function test_it_creates_and_resolves_a_team_link(): void
    {
        // A value above the internal id range keeps this deterministic on a
        // database that already contains real teams.
        $externalId = 2147483000;
        $team = $this->createTeam('Al Ahly', $externalId);
        $code = $this->createLink('team', $externalId, '1');

        $resolved = $this->getJson('/api/v1/deeplinks/resolve?code=' . $code)
            ->assertStatus(200)
            ->json('data');

        $this->assertSame('team', $resolved['target_type']);
        $this->assertSame($team->id, $resolved['target_id']);
        $this->assertSame($externalId, $resolved['team_id']);
        $this->assertSame('1', $resolved['screen']);
        $this->assertSame('Al Ahly', $resolved['title']);
        $this->assertSame('/team/' . $team->id, $resolved['path']);
        $this->assertArrayNotHasKey('match_id', $resolved);
        $this->assertArrayNotHasKey('fixture_id', $resolved);
        $this->assertArrayNotHasKey('competition_id', $resolved);

        // The external Football-API id must reuse the same code.
        $again = $this->postJson('/api/v1/deeplinks', [
            'type'   => 'team',
            'id'     => $externalId,
            'screen' => '1',
        ])->assertStatus(200)->json('data');

        $this->assertSame($code, $again['code']);
    }

    public function test_it_creates_and_resolves_a_screen_link(): void
    {
        $code = $this->createLink('screen', 2, '2');

        $resolved = $this->getJson('/api/v1/deeplinks/resolve?code=' . $code)
            ->assertStatus(200)
            ->json('data');

        $this->assertSame('screen', $resolved['target_type']);
        $this->assertSame(2, $resolved['target_id']);
        $this->assertSame('2', $resolved['screen']);
        $this->assertSame('/screen/2', $resolved['path']);
        $this->assertStringContainsString('Match lineup tab', $resolved['title']);
    }

    public function test_screen_comes_from_the_create_body_and_is_null_when_omitted(): void
    {
        $match = $this->createMatch();

        $withScreen = $this->postJson('/api/v1/deeplinks', [
            'type'   => 'match',
            'id'     => $match->fixture_id,
            'screen' => '1',
        ])->assertStatus(200)->json('data');

        $this->assertSame('1', $withScreen['screen']);

        $withoutScreen = $this->postJson('/api/v1/deeplinks', [
            'type' => 'match',
            'id'   => $match->fixture_id,
        ])->assertStatus(200)->json('data');

        $this->assertArrayHasKey('screen', $withoutScreen);
        $this->assertNull($withoutScreen['screen']);

        // Integer screen values are accepted and stored as strings.
        $asInteger = $this->postJson('/api/v1/deeplinks', [
            'type'   => 'match',
            'id'     => $match->fixture_id,
            'screen' => 2,
        ])->assertStatus(200)->json('data');

        $this->assertSame('2', $asInteger['screen']);
    }

    public function test_same_target_with_different_screens_gets_different_codes(): void
    {
        $match = $this->createMatch();

        $first = $this->postJson('/api/v1/deeplinks', [
            'type'   => 'match',
            'id'     => $match->fixture_id,
            'screen' => '1',
        ])->assertStatus(200)->json('data');

        $second = $this->postJson('/api/v1/deeplinks', [
            'type'   => 'match',
            'id'     => $match->fixture_id,
            'screen' => '2',
        ])->assertStatus(200)->json('data');

        $this->assertNotSame($first['code'], $second['code']);

        // The same target + screen still reuses the same code.
        $again = $this->postJson('/api/v1/deeplinks', [
            'type'   => 'match',
            'id'     => $match->fixture_id,
            'screen' => '1',
        ])->assertStatus(200)->json('data');

        $this->assertSame($first['code'], $again['code']);

        $this->assertSame(
            '1',
            $this->getJson('/api/v1/deeplinks/resolve?code=' . $first['code'])->json('data.screen')
        );
        $this->assertSame(
            '2',
            $this->getJson('/api/v1/deeplinks/resolve?code=' . $second['code'])->json('data.screen')
        );
    }

    public function test_screen_range_zero_to_four_is_accepted(): void
    {
        foreach (range(0, 4) as $screen) {
            $created = $this->postJson('/api/v1/deeplinks', [
                'type'   => 'screen',
                'id'     => $screen,
                'screen' => (string) $screen,
            ])->assertStatus(200)->json('data');

            $this->assertSame((string) $screen, $created['screen']);
            $this->assertSame($screen, $created['target_id']);

            $this->get('/screen/' . $screen)->assertStatus(200);
        }
    }

    public function test_screen_values_above_four_are_rejected(): void
    {
        $this->postJson('/api/v1/deeplinks', ['type' => 'screen', 'id' => 5])
            ->assertStatus(422);

        $this->get('/screen/5')->assertStatus(404);

        $match = $this->createMatch();
        $this->postJson('/api/v1/deeplinks', [
            'type'   => 'match',
            'id'     => $match->fixture_id,
            'screen' => '5',
        ])->assertStatus(422);
    }

    public function test_unknown_screen_value_is_rejected(): void
    {
        $match = $this->createMatch();

        $this->postJson('/api/v1/deeplinks', [
            'type'   => 'match',
            'id'     => $match->fixture_id,
            'screen' => '99',
        ])->assertStatus(422);
    }

    public function test_unknown_screens_are_rejected(): void
    {
        $this->postJson('/api/v1/deeplinks', ['type' => 'screen', 'id' => 99])
            ->assertStatus(422);

        $this->get('/screen/99')->assertStatus(404);
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

        $this->get('/screen/1')
            ->assertStatus(200)
            ->assertSee('League / team screen')
            ->assertSee('/screen/1', false);
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
            // A value above the internal id range keeps this deterministic on
            // a database that already contains real competitions.
            'league_id'  => 2147483000,
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

    private function createLink(string $type, int $id, ?string $screen = null): string
    {
        return app(\App\Services\DeepLinkService::class)->create($type, $id, $screen)->code;
    }
}
