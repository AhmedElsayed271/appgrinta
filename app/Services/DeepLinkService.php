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
    /** Content types that are backed by a database model. */
    public const TYPES = [
        'post'   => Post::class,
        'league' => Competition::class,
        'match'  => Matche::class,
        'team'   => Team::class,
    ];

    /** A screen link has no content model: its target id IS the screen number. */
    public const SCREEN_TYPE = 'screen';

    public function allowedTypes(): array
    {
        return array_merge(array_keys(self::TYPES), [self::SCREEN_TYPE]);
    }

    public function screens(): array
    {
        return (array) config('deeplinks.screens', []);
    }

    /**
     * Normalize the caller-supplied screen value to its string form.
     * Returns null for an omitted value, and also for an unknown one so the
     * controller can reject it with 422.
     */
    public function normalizeScreen(mixed $value): ?string
    {
        if (!is_int($value) && !is_string($value)) {
            return null;
        }

        $value = (string) $value;

        if ($value === '' || !ctype_digit($value)) {
            return null;
        }

        return array_key_exists((int) $value, $this->screens()) ? $value : null;
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
     * By default $id is our internal database id (used by resolve() and the
     * web landing routes). When $footballIdOnly is true — the public create
     * API — match/team ids are matched only against the Football API column
     * (fixture_id / team_id). The internal id is always stored on the link.
     */
    public function target(string $type, ?int $id, bool $footballIdOnly = false): ?Model
    {
        if (!array_key_exists($type, self::TYPES) || $id === null) {
            return null;
        }

        if ($type === 'post') {
            return Post::query()->published()->find($id);
        }

        $class = self::TYPES[$type];
        $externalColumn = $this->externalIdColumn($type);

        if ($externalColumn !== null && $footballIdOnly) {
            // The create API accepts only Football API ids for match/team.
            return $class::query()->where($externalColumn, $id)->first();
        }

        // resolve() and the web landing routes always use our internal id.
        return $class::query()->find($id);
    }

    private function externalIdColumn(string $type): ?string
    {
        return match ($type) {
            'match'  => 'fixture_id',
            'team'   => 'team_id',
            'league' => 'league_id',
            default  => null,
        };
    }

    public function create(string $type, ?int $id, ?string $screen = null): ?DeepLink
    {
        if ($type === self::SCREEN_TYPE) {
            if ($id === null || !array_key_exists($id, $this->screens())) {
                return null;
            }
        } else {
            $target = $this->target($type, $id, true);

            if (!$target) {
                return null;
            }

            // Always store the internal id, even though the caller supplied
            // the Football API id.
            $id = (int) $target->id;
        }

        // The screen is part of the identity: the same target opened on two
        // different screens produces two distinct codes.
        return DeepLink::query()->firstOrCreate(
            ['target_type' => $type, 'target_id' => $id, 'screen' => $screen],
            ['code' => $this->generateCode()]
        );
    }

    public function resolve(?string $code): ?array
    {
        $link = $this->findByCode($code);

        if (!$link) {
            return null;
        }

        if ($link->target_type === self::SCREEN_TYPE) {
            if (!array_key_exists((int) $link->target_id, $this->screens())) {
                return null;
            }

            return $this->payload($link, null);
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

    /**
     * Share-page data for any target type. Screens pass a null model.
     */
    public function viewDataForTarget(string $type, int $id, ?Model $target = null): array
    {
        if ($type === self::SCREEN_TYPE && !array_key_exists($id, $this->screens())) {
            return [];
        }

        $appParams = $type === self::SCREEN_TYPE
            ? ['type' => $type, 'screen' => (string) $id]
            : ['type' => $type, 'id' => $id];

        return $this->viewDataForLink([
            'title'         => $this->titleFor($target, $type, $id),
            'description'   => $this->descriptionFor($target, $type, $id),
            'image'         => $this->imageFor($target),
            'share_url'     => $this->webUrl($this->targetPath($type, $id)),
            'app_url'       => $this->appUrl($appParams),
            'play_url'      => $this->playUrl(null),
            'app_store_url' => $this->appStoreUrl(),
            'code'          => null,
        ]);
    }

    private function payload(DeepLink $link, ?Model $target): array
    {
        $type = $link->target_type;
        $id   = (int) $link->target_id;
        $url  = $this->webUrl('/l/' . $link->code);

        $data = [
            'code'          => $link->code,
            'claim_code'    => $link->code,
            'target_type'   => $type,
            'target_id'     => $id,
            'title'         => $this->titleFor($target, $type, $id),
            'description'   => $this->descriptionFor($target, $type, $id),
            'image'         => $this->imageFor($target),
            'path'          => $this->targetPath($type, $id),
            'web_url'       => $url,
            'share_url'     => $url,
            'app_url'       => $this->appUrl(['code' => $link->code]),
            'play_url'      => $this->playUrl($link->code),
            'app_store_url' => $this->appStoreUrl(),
        ];

        // The old contract (target_type + target_id) is kept. `screen` is now
        // always present as a key, holding exactly what the caller stored at
        // create time (null when omitted). Ids are additive helpers.
        return $data + $this->navigationFor($target, $link->screen);
    }

    private function navigationFor(?Model $target, ?string $screen): array
    {
        // Keys that merely repeat target_id (post_id, match_id, and
        // competition_id on a league link) are intentionally not emitted.
        // Only ids that differ from target_id are kept as helpers.
        $ids = [];

        if ($target instanceof Competition) {
            // External Football-API league id; target_id holds our internal id.
            $ids = ['league_id' => (int) $target->league_id];
        }

        if ($target instanceof Matche) {
            $ids = [
                'fixture_id'     => (int) $target->fixture_id,
                'home_team_id'   => (int) $target->team1_id,
                'away_team_id'   => (int) $target->team2_id,
                'competition_id' => (int) $target->competition_id,
                'match_date'     => $target->match_date?->toIso8601String(),
                'status'         => (string) $target->status,
            ];
        } elseif ($target instanceof Team) {
            // External Football-API id; target_id holds the internal id.
            $ids = ['team_id' => (int) $target->team_id];
        }

        // Non-applicable id keys are omitted, never sent as null.
        $ids = array_filter($ids, static fn ($value) => $value !== null && $value !== '' && $value !== 0);

        return ['screen' => $screen] + $ids;
    }

    private function generateCode(): string
    {
        do {
            $code = 'GR-' . strtoupper(Str::random(8));
        } while (DeepLink::query()->where('code', $code)->exists());

        return $code;
    }

    private function titleFor(?Model $target, string $type, int $id): string
    {
        if ($type === self::SCREEN_TYPE) {
            return 'Grinta - ' . ($this->screens()[$id] ?? 'Screen ' . $id);
        }

        if (!$target) {
            return 'Grinta';
        }

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

    private function descriptionFor(?Model $target, string $type, int $id): string
    {
        if ($type === self::SCREEN_TYPE) {
            $description = $this->screens()[$id] ?? 'Grinta';
        } elseif ($target instanceof Post) {
            $description = $target->translate(app()->getLocale())?->description;
        } elseif ($target instanceof Matche) {
            $description = 'Grinta - ' . $this->titleFor($target, $type, $id);

            if ($target->match_date) {
                $description .= ' - ' . $target->match_date->format('Y-m-d H:i');
            }
        } elseif ($target) {
            $description = 'Grinta - ' . $this->titleFor($target, $type, $id);
        } else {
            $description = 'Grinta';
        }

        return Str::limit(trim(strip_tags((string) $description)), 160);
    }

    private function imageFor(?Model $target): string
    {
        $image = $target->image_path ?? '';

        return is_string($image) && $image !== ''
            ? $image
            : $this->webUrl('/storage/uploads/default.png');
    }

    private function targetPath(string $type, int $id): string
    {
        return match ($type) {
            'post'              => '/post/' . $id,
            'league'            => '/league/' . $id,
            'match'             => '/match/' . $id,
            'team'              => '/team/' . $id,
            self::SCREEN_TYPE   => '/screen/' . $id,
            default             => '/l/' . $id,
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
