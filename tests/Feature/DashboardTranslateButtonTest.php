<?php

namespace Tests\Feature;

use App\Http\Middleware\Authenticate;
use App\Http\Middleware\RoleMiddleware;
use App\Services\TranslationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationViewPath;
use Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect;
use Tests\TestCase;

/**
 * Coverage for the "ترجمة" button added to the five manual-broadcast pages
 * (post / match / url / team / competition) and for the dashboard.translate
 * endpoint the button calls.
 */
class DashboardTranslateButtonTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        // The localization middleware can redirect a bare request before the
        // view renders; auth is not what this test is about.
        $this->withoutMiddleware([
            Authenticate::class,
            RoleMiddleware::class,
            LocaleSessionRedirect::class,
            LaravelLocalizationRedirectFilter::class,
            LaravelLocalizationViewPath::class,
        ]);
    }

    private function pages(): array
    {
        return [
            'post'        => '/en/dashboard/notifications/post',
            'match'       => '/en/dashboard/notifications/match',
            'url'         => '/en/dashboard/notifications/url',
            'team'        => '/en/dashboard/notifications/team',
            'competition' => '/en/dashboard/notifications/competition',
        ];
    }

    public function test_every_notification_page_renders_translate_buttons_and_handler(): void
    {
        foreach ($this->pages() as $name => $uri) {
            $html = $this->get($uri)
                ->assertOk()
                ->getContent();

            $message = "page {$name}";

            // Buttons exist — exactly one for the name, one for description
            // (they are only rendered inside the @if($locale === 'en') block).
            $this->assertSame(
                1,
                substr_count($html, 'data-field="name"'),
                "{$message}: expected exactly one translate button for the name field"
            );
            $this->assertSame(
                1,
                substr_count($html, 'data-field="description"'),
                "{$message}: expected exactly one translate button for the description field"
            );

            // Buttons point at the arabic source / english target
            // (one pair per button => name + description).
            $this->assertSame(
                2,
                substr_count($html, 'data-source="ar" data-target="en"'),
                "{$message}: buttons must translate ar => en"
            );

            // The JS handler that wires the button to the endpoint.
            $this->assertNotFalse(
                strpos($html, "$('.btn-translate').on('click'"),
                "{$message}: missing the .btn-translate click handler"
            );

            // The endpoint URL embedded in the AJAX call.
            $this->assertNotFalse(
                strpos($html, 'dashboard/translate'),
                "{$message}: missing the dashboard.translate URL"
            );

            // The fields the JS writes into must exist for both locales.
            foreach (['id="ar_name"', 'id="en_name"', 'id="ar_description"', 'id="en_description"'] as $needle) {
                $this->assertNotFalse(
                    strpos($html, $needle),
                    "{$message}: missing {$needle}"
                );
            }
        }
    }

    public function test_translate_endpoint_returns_translation(): void
    {
        $this->mock(TranslationService::class, function ($mock) {
            $mock->shouldReceive('translate')
                ->once()
                ->with('مرحبا بالعالم', 'ar', 'en')
                ->andReturn('Hello world');
        });

        $this->postJson('/en/dashboard/translate', [
            'text'   => 'مرحبا بالعالم',
            'source' => 'ar',
            'target' => 'en',
        ])->assertOk()->assertJson([
            'success'     => true,
            'translation' => 'Hello world',
        ]);
    }

    public function test_translate_endpoint_defaults_source_to_ar(): void
    {
        $this->mock(TranslationService::class, function ($mock) {
            $mock->shouldReceive('translate')
                ->once()
                ->with('مباراة', 'ar', 'en')
                ->andReturn('Match');
        });

        // source omitted => controller must fall back to 'ar'.
        $this->postJson('/en/dashboard/translate', [
            'text'   => 'مباراة',
            'target' => 'en',
        ])->assertOk()->assertJson([
            'success'     => true,
            'translation' => 'Match',
        ]);
    }

    public function test_translate_endpoint_reports_failed_translation(): void
    {
        $this->mock(TranslationService::class, function ($mock) {
            $mock->shouldReceive('translate')->once()->andReturnNull();
        });

        $this->postJson('/en/dashboard/translate', [
            'text'   => 'نص',
            'source' => 'ar',
            'target' => 'en',
        ])->assertOk()->assertJson([
            'success' => false,
        ]);
    }

    public function test_translate_endpoint_requires_text(): void
    {
        $this->postJson('/en/dashboard/translate', [
            'source' => 'ar',
            'target' => 'en',
        ])->assertStatus(422);
    }

    public function test_translate_endpoint_requires_target(): void
    {
        $this->postJson('/en/dashboard/translate', [
            'text' => 'نص',
        ])->assertStatus(422);
    }
}
