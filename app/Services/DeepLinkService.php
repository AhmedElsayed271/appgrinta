<?php

namespace App\Services;

use App\Models\Competition;
use App\Models\DeepLink;
use App\Models\Post;
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
    ];

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
     */
    public function target(string $type, int $id): ?Model
    {
        if ($type === 'post') {
            return Post::query()->published()->find($id);
        }

        if ($type === 'league') {
            return Competition::query()->find($id);
        }

        return null;
    }

    public function create(string $type, int $targetId): ?DeepLink
    {
        if (!array_key_exists($type, self::TYPES) || !$this->target($type, $targetId)) {
            return null;
        }

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
        $id   = (int) $target->id;
        $path = $this->targetPath($type, $id);

        return [
            'title'        => $this->titleFor($target),
            'description'  => $this->descriptionFor($target),
            'image'        => $this->imageFor($target),
            'shareUrl'     => $this->webUrl($path),
            'appUrl'       => $this->appUrl(['type' => $type, 'id' => $id]),
            'playUrl'      => $this->playUrl(null),
            'appStoreUrl'  => $this->appStoreUrl(),
            'claimCode'    => null,
        ];
    }

    private function payload(DeepLink $link, Model $target): array
    {
        $type = $link->target_type;
        $id   = (int) $link->target_id;
        $url  = $this->webUrl('/l/' . $link->code);

        return [
            'code'          => $link->code,
            'claim_code'    => $link->code,
            'target_type'   => $type,
            'target_id'     => $id,
            'title'         => $this->titleFor($target),
            'description'   => $this->descriptionFor($target),
            'image'         => $this->imageFor($target),
            'path'          => $this->targetPath($type, $id),
            'web_url'       => $url,
            'share_url'     => $url,
            'app_url'       => $this->appUrl(['code' => $link->code]),
            'play_url'      => $this->playUrl($link->code),
            'app_store_url' => $this->appStoreUrl(),
        ];
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
        $name = $target->translate(app()->getLocale())?->name;

        if (empty($name)) {
            $name = $target->translate('en')?->name;
        }

        return $name ?: 'Grinta';
    }

    private function descriptionFor(Model $target): string
    {
        if ($target instanceof Post) {
            $description = $target->translate(app()->getLocale())?->description;
        } else {
            $description = 'Grinta - ' . ($this->titleFor($target));
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
        return $type === 'post' ? '/post/' . $id : '/league/' . $id;
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
