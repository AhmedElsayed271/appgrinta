<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Models\Client;
use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendNotificationWeeklyMatches extends Command
{
    protected $signature   = 'notification:weekly {league?}';
    protected $description = "Store this week's match data per league at 07:30 every Sunday in each client's local timezone";

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $setting = Setting::find(1);
        $leagues = array_map('trim', explode(',', $setting->leagues));

        // ── Direct call with explicit league argument ──────────────────
        if ($this->argument('league') !== null) {
            return $this->fetchAndStore($setting, $this->argument('league'));
        }

        // ── Fan-out: check every timezone, fire on Sunday at 07:30 ────
        $timezones = Client::cachedTimezones();
        $fetched   = [];

        foreach ($timezones as $timezone) {
            try {
                $localNow = Carbon::now($timezone);
            } catch (\Exception $e) {
                Log::warning('WeeklyMatches: invalid timezone skipped', [
                    'timezone' => $timezone,
                ]);
                continue;
            }

            if (
                $localNow->dayOfWeek !== Carbon::SUNDAY ||
                $localNow->hour      !== 7              ||
                $localNow->minute    !== 30
            ) {
                continue;
            }

            // ── Skip duplicate UTC offsets ─────────────────────────────
            $utcOffset = $localNow->utcOffset();
            if (in_array($utcOffset, $fetched)) {
                continue;
            }
            $fetched[] = $utcOffset;

            foreach ($leagues as $league) {
                try {
                    $this->fetchAndStore($setting, $league, $localNow);
                } catch (\Exception $e) {
                    Log::error('WeeklyMatches fetch failed', [
                        'league'   => $league,
                        'timezone' => $timezone,
                        'error'    => $e->getMessage(),
                    ]);
                }
            }
        }

        return 0;
    }

    protected function fetchAndStore($setting, string $league, ?Carbon $reference = null): int
    {
        $reference = $reference ?? Carbon::now();

        $from = $reference->copy()->format('Y-m-d');
        $to   = $reference->copy()->addWeek()->format('Y-m-d');

        $response = Http::withHeaders([
            'x-rapidapi-key' => $setting->football_key,
        ])->get('https://v3.football.api-sports.io/fixtures', [
            'timezone' => 'UTC',
            'league'   => $league,
            'from'     => $from,
            'to'       => $to,
            'season'   => $setting->season,
        ])->json();

        if (!empty($response['errors'])) {
            Log::error('WeeklyMatches API error', [
                'league' => $league,
                'errors' => $response['errors'],
            ]);
            return 1;
        }

        $responseData = $response['response'] ?? [];

        // ── Upsert replaces manual insert/update check ────────────────
        DB::table('weekly_matches')->upsert(
            [
                [
                    'league_id' => $league,
                    'response'  => json_encode($responseData),
                ]
            ],
            ['league_id'],  // unique key to match on
            ['response']    // columns to update if exists
        );

        Log::info('WeeklyMatches stored', [
            'league' => $league,
            'from'   => $from,
            'to'     => $to,
            'count'  => count($responseData),
        ]);

        return 0;
    }
}