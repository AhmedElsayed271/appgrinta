<?php

namespace App\Services;

use App\Models\Competition;
use App\Models\DeepLink;
use App\Models\Matche;
use App\Models\Post;
use App\Models\Team;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Self-hosted replacement for Firebase Dynamic Links.
 *
 * Codes are opaque: the target is always read from the database, never from a
 * query string, so a shared link cannot be turned into an open redirect.
 */
class DeepLinkService
{
    public const TYPES = [
        'post'   => Post::class,
        'league' => Competition::class,
        'match'  => Matche::class,
        'team'   => Team::class,
    ];

    /**
     * `screen` is a navigation field, not a link type. Values follow the
     * push-notification contract: 1 = league/team, 2 = match lineup tab.
     * Posts have no screen value and omit the key.
     */
    private const SCREEN_BY_TYPE = [
        'league' => '1',
        'team'   => '1',
        'match'  => '2',
    ];

    public function allowedTypes(): array
    {
        return array_keys(self::TYPES);
    }

    public function normalizeCode(?string $code): ?string
    {
        if (!is_string($code)) {
            return null;
        }

        $code = strtoupper(trim($code));
        $code = preg_replace('/[^A-Z0-9-]/', '', $code) ?: '';

        if ($code === '') {
            return null;
        }

        return str_starts_with($code, 'GR-') ? $code : 'GR-' . $code;
    }

    public function findByCode(?string $code): ?DeepLink
    {
        $code = $this->normalizeCode($code);

        if ($code === null) {
            return null;
        }

        return DeepLink::query()->where('code', $code)->first();
    }

    /**
     * A target is public only when the content exists and, for posts, is
     * already published. Scheduled posts must not leak through shared links.
     *
     * For teams both the internal id and the external Football-API team_id are
     * accepted, matching TeamsController::show().
     */
    public function target(string $type, int $id): ?Model
    {
        if (!array_key_exists($type, self::TYPES)) {
            return null;
        }

        if ($type === 'post') {
            return Post::query()->published()->find($id);
        }

        if ($type === 'team') {
            // Prefer the internal id; an OR query could otherwise return a
            // different team whose external team_id equals this internal id.
            return Team::query()->find($id)
                ?? Team::query()->where('team_id', $id)->first();
        }

        $class = self::TYPES[$type];

        return $class::query()->find($id);
    }

    public function create(string $type, int $targetId): ?DeepLink
    {
        $target = $this->target($type, $targetId);

        if (!$target) {
            return null;
        }

        // Always store the internal id so an external team id and the
        // matching database id reuse the same code.
        $targetId = (int) $target->id;

        return DeepLink::query()->firstOrCreate(
            ['target_type' => $type, 'target_id' => $targetId],
            ['code' => $this->generateCode()]
        );
    }

    public function resolve(?string $code): ?array
    {
        $link = $this->findByCode($code);

        if (!$link) {
            return null;
        }

        $target = $this->target($link->target_type, (int) $link->target_id);

        if (!$target) {
            return null;
        }

        return $this->payload($link, $target);
    }

    public function registerClick(?string $code): ?array
    {
        $link = $this->findByCode($code);

        if (!$link) {
            return null;
        }

        $link->increment('clicks_count');
        $link->forceFill(['last_clicked_at' => now()])->save();

        return $this->resolve($link->code);
    }

    /**
     * Data used by the web landing/share page.
     */
    public function viewDataForLink(array $payload): array
    {
        return [
            'title'        => $payload['title'],
            'description'  => $payload['description'],
            'image'        => $payload['image'],
            'shareUrl'     => $payload['share_url'],
            'appUrl'       => $payload['app_url'],
            'playUrl'      => $payload['play_url'],
            'appStoreUrl'  => $payload['app_store_url'],
            'claimCode'    => $payload['code'],
        ];
    }

    public function viewDataForTarget(string $type, Model $target): array
    {
        $id = (int) $target->id;

        return $this->viewDataForLink([
            'title'         => $this->titleFor($target),
            'description'   => $this->descriptionFor($target, $type),
            'image'         => $this->imageFor($target),
            'share_url'     => $this->webUrl($this->targetPath($type, $id)),
            'app_url'       => $this->appUrl(['type' => $type, 'id' => $id]),
            'play_url'      => $this->playUrl(null),
            'app_store_url' => $this->appStoreUrl(),
            'code'          => null,
        ]);
    }

    private function payload(DeepLink $link, Model $target): array
    {
        $type = $link->target_type;
        $id   = (int) $target->id;
        $url  = $this->webUrl('/l/' . $link->code);

        $data = [
            'code'          => $link->code,
            'claim_code'    => $link->code,
            'title'         => $this->titleFor($target),
            'description'   => $this->descriptionFor($target, $type),
            'image'         => $this->imageFor($target),
            'path'          => $this->targetPath($type, $id),
            'web_url'       => $url,
            'share_url'     => $url,
            'app_url'       => $this->appUrl(['code' => $link->code]),
            'play_url'      => $this->playUrl($link->code),
            'app_store_url' => $this->appStoreUrl(),
        ];

        // No target_type/target_id: the app navigates with `screen` + the
        // concrete id keys. Keys that do not apply are omitted entirely.
        return $data + $this->navigationFor($type, $target);
    }

    private function navigationFor(string $type, Model $target): array
    {
        $ids = match ($type) {
            'post'   => ['post_id' => (int) $target->id],
            'league' => ['competition_id' => (int) $target->id],
            'team'   => ['team_id' => (int) $target->team_id],
            'match'  => [
                'match_id'       => (int) $target->id,
                'fixture_id'     => (int) $target->fixture_id,
                'home_team_id'   => (int) $target->team1_id,
                'away_team_id'   => (int) $target->team2_id,
                'competition_id' => (int) $target->competition_id,
                'match_date'     => $target->match_date?->toIso8601String(),
                'status'         => (string) $target->status,
            ],
            default  => [],
        };

        $ids = array_filter($ids, static fn ($value) => $value !== null && $value !== '' && $value !== 0);

        $screen = self::SCREEN_BY_TYPE[$type] ?? null;

        return ($screen !== null ? ['screen' => $screen] : []) + $ids;
    }

    private function generateCode(): string
    {
        do {
            $code = 'GR-' . strtoupper(Str::random(8));
        } while (DeepLink::query()->where('code', $code)->exists());

        return $code;
    }

    private function titleFor(Model $target): string
    {
        if ($target instanceof Matche) {
            $home  = $this->teamName($target->team1);
            $away  = $this->teamName($target->team2);
            $title = trim($home . ($home !== '' && $away !== '' ? ' vs ' : '') . $away);

            return $title !== '' ? $title : 'Grinta';
        }

        $name = $target->translate(app()->getLocale())?->name;

        if (empty($name)) {
            $name = $target->translate('en')?->name;
        }

        return $name ?: 'Grinta';
    }

    private function teamName(?Team $team): string
    {
        if (!$team) {
            return '';
        }

        $name = $team->translate(app()->getLocale())?->name;

        if (empty($name)) {
            $name = $team->translate('en')?->name;
        }

        return (string) $name;
    }

    private function descriptionFor(Model $target, string $type): string
    {
        if ($target instanceof Post) {
            $description = $target->translate(app()->getLocale())?->description;
        } elseif ($target instanceof Matche) {
            $description = 'Grinta - ' . $this->titleFor($target);

            if ($target->match_date) {
                $description .= ' - ' . $target->match_date->format('Y-m-d H:i');
            }
        } else {
            $description = 'Grinta - ' . $this->titleFor($target);
        }

        return Str::limit(trim(strip_tags((string) $description)), 160);
    }

    private function imageFor(Model $target): string
    {
        $image = $target->image_path ?? '';

        return is_string($image) && $image !== ''
            ? $image
            : $this->webUrl('/storage/uploads/default.png');
    }

    private function targetPath(string $type, int $id): string
    {
        return match ($type) {
            'post'   => '/post/' . $id,
            'league' => '/league/' . $id,
            'match'  => '/match/' . $id,
            'team'   => '/team/' . $id,
            default  => '/l/' . $id,
        };
    }

    private function webUrl(string $path): string
    {
        $base = rtrim((string) (config('deeplinks.web_base_url') ?: url('/')), '/');

        return $base . '/' . ltrim($path, '/');
    }

    private function appUrl(array $params): ?string
    {
        $scheme = trim((string) config('deeplinks.app_scheme'));

        if ($scheme === '') {
            return null;
        }

        return $scheme . '://open?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    }

    private function playUrl(?string $code): ?string
    {
        $base = config('deeplinks.play_store_url');

        if (!$base) {
            $package = config('deeplinks.android_package');

            if (!$package) {
                return null;
            }

            $base = 'https://play.google.com/store/apps/details?id=' . $package;
        }

        if ($code === null) {
            return $base;
        }

        // Play Install Referrer reads this value after a fresh install.
        return $this->appendQuery($base, ['referrer' => 'dl=' . $code]);
    }

    private function appStoreUrl(): ?string
    {
        $url = config('deeplinks.app_store_url');

        if (!is_string($url) || $url === '') {
            return null;
        }

        // Until the real App Store URL exists it may temporarily point at
        // Google Play. Do not show a misleading second "App Store" button.
        if (str_contains($url, 'play.google.com') || $url === $this->playUrl(null)) {
            return null;
        }

        return $url;
    }

    private function appendQuery(string $url, array $query): string
    {
        $separator = str_contains($url, '?') ? '&' : '?';

        return $url . $separator . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }
}
