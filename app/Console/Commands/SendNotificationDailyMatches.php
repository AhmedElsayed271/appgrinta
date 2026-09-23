<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Models\Client;
use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendNotificationDailyMatches extends Command
{
    protected $signature   = 'notification:daily {league?}';
    protected $description = 'Store today\'s match data per league at 07:00 in each client\'s local timezone';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $setting = Setting::find(1);

        // ── Direct call with explicit league argument ──────────────────
        if ($this->argument('league') !== null) {
            return $this->fetchAndStore($setting, $this->argument('league'));
        }

        // ── Fan-out: check every timezone and run at 07:00 local time ─
        $timezones = Client::cachedTimezones();
        $leagues   = array_map('trim', explode(',', $setting->leagues));

        foreach ($timezones as $timezone) {
            try {
                $localNow = Carbon::now($timezone);
            } catch (\Exception $e) {
                Log::warning('Invalid timezone skipped: ' . $timezone);
                continue;
            }

            if ($localNow->hour !== 7 || $localNow->minute !== 0) {
                continue;
            }

            $localToday = $localNow->format('Y-m-d');

            foreach ($leagues as $league) {
                try {
                    $this->fetchAndStore($setting, $league, $localToday);
                } catch (\Exception $e) {
                    Log::error('DailyMatches fetch failed', [
                        'league'   => $league,
                        'timezone' => $timezone,
                        'error'    => $e->getMessage(),
                    ]);
                }
            }
        }

        return 0;
    }

    protected function fetchAndStore($setting, string $league, ?string $date = null): int
    {
        $date = $date ?? Carbon::now()->format('Y-m-d');

        $response = Http::withHeaders([
            'x-rapidapi-key' => $setting->football_key,
        ])->get('https://v3.football.api-sports.io/fixtures', [
            'date'     => $date,
            'timezone' => 'UTC',
            'league'   => $league,
            'season'   => $setting->season,
        ])->json();

        if (!empty($response['errors'])) {
            Log::error('DailyMatches API error', [
                'league' => $league,
                'errors' => $response['errors'],
            ]);
            return 1;
        }

        $responseData = $response['response'] ?? [];

        // ── Upsert replaces manual insert/update check ────────────────
        DB::table('daily_matches')->upsert(
            [
                [
                    'league_id' => $league,
                    'response'  => json_encode($responseData),
                ]
            ],
            ['league_id'],   // unique key to match on
            ['response']     // columns to update if exists
        );

        Log::info('DailyMatches stored', [
            'league' => $league,
            'date'   => $date,
            'count'  => count($responseData),
        ]);

        return 0;
    }
}