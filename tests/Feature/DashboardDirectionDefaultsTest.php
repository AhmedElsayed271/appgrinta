<?php

namespace Tests\Feature;

use App\Http\Middleware\Authenticate;
use App\Http\Middleware\RoleMiddleware;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Testing\TestResponse;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationViewPath;
use Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect;
use Tests\TestCase;

/**
 * Alignment / direction defaults: the document must be rtl in Arabic and ltr
 * in English, the form fields must follow their own language, and the
 * summernote editors must open with the right direction and text alignment.
 */
class DashboardDirectionDefaultsTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            Authenticate::class,
            RoleMiddleware::class,
            LocaleSessionRedirect::class,
            LaravelLocalizationRedirectFilter::class,
            LaravelLocalizationViewPath::class,
        ]);
    }

    /**
     * Production resolves the locale per request from the URL during route
     * registration (routes/web.php calls LaravelLocalization::setLocale()).
     * Tests register routes once at boot with an empty request, so mirror the
     * same call here before dispatching.
     */
    private function localizedGet(string $uri, string $locale): TestResponse
    {
        app('laravellocalization')->setLocale($locale);

        return $this->get($uri);
    }

    private function pages(): array
    {
        return [
            'post'        => '/dashboard/notifications/post',
            'match'       => '/dashboard/notifications/match',
            'url'         => '/dashboard/notifications/url',
            'team'        => '/dashboard/notifications/team',
            'competition' => '/dashboard/notifications/competition',
        ];
    }

    public function test_document_direction_follows_the_ui_locale(): void
    {
        foreach ($this->pages() as $name => $uri) {
            $rtl = $this->localizedGet('/ar' . $uri, 'ar')->assertOk()->getContent();
            $ltr = $this->localizedGet('/en' . $uri, 'en')->assertOk()->getContent();

            $this->assertMatchesRegularExpression('/<html[^>]*\sdir="rtl"/', $rtl, "{$name}: ar page must be rtl");
            $this->assertMatchesRegularExpression('/<html[^>]*\sdir="ltr"/', $ltr, "{$name}: en page must be ltr");
        }
    }

    public function test_fields_follow_their_own_language_direction(): void
    {
        foreach ($this->pages() as $name => $uri) {
            $html = $this->localizedGet('/en' . $uri, 'en')->assertOk()->getContent();

            $message = "page {$name}";

            // The arabic group renders dir="rtl", the english group dir="ltr".
            $this->assertNotFalse(
                strpos($html, 'dir="rtl"'),
                "{$message}: missing rtl fields for the arabic group"
            );
            $this->assertNotFalse(
                strpos($html, 'dir="ltr"'),
                "{$message}: missing ltr fields for the english group"
            );
        }
    }

    public function test_summernote_editors_open_with_language_direction_and_alignment(): void
    {
        foreach ($this->pages() as $name => $uri) {
            $html = $this->localizedGet('/en' . $uri, 'en')->assertOk()->getContent();

            $message = "page {$name}";

            // .note-editable dir set per field group (ar => rtl, en => ltr).
            $this->assertNotFalse(
                strpos($html, ".attr('dir', 'rtl')"),
                "{$message}: arabic editor must open rtl"
            );
            $this->assertNotFalse(
                strpos($html, ".attr('dir', 'ltr')"),
                "{$message}: english editor must open ltr"
            );

            // Matching text alignment (right for arabic, left for english).
            $this->assertNotFalse(
                strpos($html, ".css('text-align', 'right')"),
                "{$message}: arabic editor must be right aligned"
            );
            $this->assertNotFalse(
                strpos($html, ".css('text-align', 'left')"),
                "{$message}: english editor must be left aligned"
            );
        }
    }

    public function test_rtl_editor_script_is_loaded(): void
    {
        foreach ($this->pages() as $name => $uri) {
            $html = $this->localizedGet('/en' . $uri, 'en')->assertOk()->getContent();

            $this->assertNotFalse(
                strpos($html, 'summernote-ext-rtl.js'),
                "page {$name}: summernote rtl extension must be loaded"
            );
        }
    }
}
