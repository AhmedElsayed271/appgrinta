<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Cache;

class Client extends Authenticatable
{
    use HasFactory;

    protected $fillable = [
        'full_name',
        'email',
        'password',
        'api_token',
        'verified_code',
        'email_verified_at',
        'fb_token',
        'social_id',
        'locale',
        'timezone',
    ];

    const SEARCHFIELDS = [
        'full_name',
        'email',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'action',
    ];

    protected $appends = ['action'];

    // ── Timezone cache ────────────────────────────────────────────────────

    /**
     * Cache key for the distinct timezone list.
     */
    const TIMEZONES_CACHE_KEY = 'clients_distinct_timezones';

    /**
     * How long to cache the timezone list (in seconds).
     * 6 hours is a safe TTL — a new client registering will invalidate it
     * immediately via the model event below anyway.
     */
    const TIMEZONES_CACHE_TTL = 21600;

    /**
     * Return all distinct, non-null timezones from the clients table.
     * Result is cached and auto-invalidated whenever any Client is
     * saved or deleted.
     *
     * Usage in commands:
     *   foreach (Client::cachedTimezones() as $timezone) { ... }
     */
    public static function cachedTimezones(): array
    {
        return Cache::remember(
            self::TIMEZONES_CACHE_KEY,
            self::TIMEZONES_CACHE_TTL,
            fn () => self::whereNotNull('timezone')
                ->whereNotNull('fb_token')
                ->distinct()
                ->pluck('timezone')
                ->toArray()
        );
    }

    /**
     * Cache key for clients grouped by timezone (full rows, used by reminder command).
     */
    const CLIENTS_BY_TIMEZONE_CACHE_KEY = 'clients_grouped_by_timezone';

    /**
     * Return all clients with a valid token grouped by timezone (full rows).
     * Used by SendReminderBefore45Minutes which needs id/locale/fb_token too.
     *
     * Usage in commands:
     *   foreach (Client::cachedClientsGroupedByTimezone() as $timezone => $clients) { ... }
     */
    public static function cachedClientsGroupedByTimezone(): \Illuminate\Support\Collection
    {
        $rows = Cache::remember(
            self::CLIENTS_BY_TIMEZONE_CACHE_KEY,
            self::TIMEZONES_CACHE_TTL,
            fn () => \Illuminate\Support\Facades\DB::table('clients')
                ->whereNotNull('timezone')
                ->whereNotNull('fb_token')
                ->select('id', 'locale', 'timezone', 'fb_token')
                ->get()
                ->toArray()
        );

        return collect($rows)->groupBy('timezone');
    }

    /**
     * Flush all client-related caches.
     * Called automatically on save/delete; can also be called manually.
     */
    public static function flushTimezoneCache(): void
    {
        Cache::forget(self::TIMEZONES_CACHE_KEY);
        Cache::forget(self::CLIENTS_BY_TIMEZONE_CACHE_KEY);
    }

    // ── Model events ──────────────────────────────────────────────────────

    protected static function booted(): void
    {
        // Invalidate the timezone cache whenever a client record changes,
        // so the next command run always sees the up-to-date timezone list.
        static::saved(fn () => self::flushTimezoneCache());
        static::deleted(fn () => self::flushTimezoneCache());
    }

    // ── Accessors / relationships ─────────────────────────────────────────

    public function getActionAttribute(): string
    {
        return '
        <a href="' . route('dashboard.clients.edit', $this->id) . '" class="btn btn-sm btn-primary btn-text-primary btn-hover-bg-success btn-icon mr-2" title="Edit details">
            <span class="svg-icon svg-icon-md">
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="24px" height="24px" viewBox="0 0 24 24" version="1.1">
                    <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                        <rect x="0" y="0" width="24" height="24"/>
                        <path d="M12.2674799,18.2323597 L12.0084872,5.45852451 C12.0004303,5.06114792 12.1504154,4.6768183 12.4255037,4.38993949 L15.0030167,1.70195304 L17.5910752,4.40093695 C17.8599071,4.6812911 18.0095067,5.05499603 18.0083938,5.44341307 L17.9718262,18.2062508 C17.9694575,19.0329966 17.2985816,19.701953 16.4718324,19.701953 L13.7671717,19.701953 C12.9505952,19.701953 12.2840328,19.0487684 12.2674799,18.2323597 Z" fill="#000000" fill-rule="nonzero" transform="translate(14.701953, 10.701953) rotate(-135.000000) translate(-14.701953, -10.701953) "/>
                        <path d="M12.9,2 C13.4522847,2 13.9,2.44771525 13.9,3 C13.9,3.55228475 13.4522847,4 12.9,4 L6,4 C4.8954305,4 4,4.8954305 4,6 L4,18 C4,19.1045695 4.8954305,20 6,20 L18,20 C19.1045695,20 20,19.1045695 20,18 L20,13 C20,12.4477153 20.4477153,12 21,12 C21.5522847,12 22,12.4477153 22,13 L22,18 C22,20.209139 20.209139,22 18,22 L6,22 C3.790861,22 2,20.209139 2,18 L2,6 C2,3.790861 3.790861,2 6,2 L12.9,2 Z" fill="#000000" fill-rule="nonzero" opacity="0.3"/>
                    </g>
                </svg>
            </span>
        </a>
        <form action="' . route('dashboard.clients.destroy', $this->id) . '" method="post" style="display: inline-block;">
        ' . csrf_field() . method_field('delete') . '
            <button type="submit" class="btn btn-sm btn-danger btn-text-primary btn-hover-warning btn-icon kt_sweetalert_demo_9" title="Delete">
                <span class="svg-icon svg-icon-md">
                    <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="24px" height="24px" viewBox="0 0 24 24" version="1.1">
                        <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                            <rect x="0" y="0" width="24" height="24"/>
                            <path d="M6,8 L6,20.5 C6,21.3284271 6.67157288,22 7.5,22 L16.5,22 C17.3284271,22 18,21.3284271 18,20.5 L18,8 L6,8 Z" fill="#000000" fill-rule="nonzero"/>
                            <path d="M14,4.5 L14,4 C14,3.44771525 13.5522847,3 13,3 L11,3 C10.4477153,3 10,3.44771525 10,4 L10,4.5 L5.5,4.5 C5.22385763,4.5 5,4.72385763 5,5 L5,5.5 C5,5.77614237 5.22385763,6 5.5,6 L18.5,6 C18.7761424,6 19,5.77614237 19,5.5 L19,5 C19,4.72385763 18.7761424,4.5 18.5,4.5 L14,4.5 Z" fill="#000000" opacity="0.3"/>
                        </g>
                    </svg>
                </span>
            </button>
        </form>
        ';
    }

    public function favouriteTeams(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(\App\Models\Team::class, 'favourite_team');
    }

    public function favouriteCompetitions(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(\App\Models\Competition::class, 'favourite_competition');
    }
}