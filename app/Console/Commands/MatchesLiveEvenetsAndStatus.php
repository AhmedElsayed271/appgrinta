<?php

namespace App\Console\Commands;

use App\Models\Matche;
use App\Models\Setting;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use App\Traits\NotificationOfMatchesTrait;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MatchesLiveEvenetsAndStatus extends Command
{
    use NotificationOfMatchesTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'live:get';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get live updates and status information of a live matches';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
            $setting = Setting::find(1);
            $league  = explode(",", $setting->leagues);
            $live    = implode("-", $league);

            $response = Http::withHeaders([
                'x-rapidapi-key' => $setting->football_key
            ])
            ->timeout(60)
            ->get('https://v3.football.api-sports.io/fixtures', [
                'timezone' => 'UTC',
                'live'     => $live,
            ]);

            $data = $response->json("response");

            if (!empty($data)) {
                foreach ($data as $value) {
                    try {
                        $match = Matche::where('fixture_id', $value["fixture"]["id"])->first();

                        if (!$match) {
                            continue;
                        }

                        $matches = $this->matchArrayFromFixtureId(
                            $value["fixture"]["id"],
                            $value['goals']['home'],
                            $value['goals']['away']
                        );

                        foreach ($value['events'] as $event) {
                            $this->events($event, $matches, $value["fixture"]["status"]["elapsed"], $match->id);
                        }

                        // While a period is in play the API value is taken as-is
                        // (so a new half self-cleans); the last known stoppage is
                        // only kept for a period that has just ended (HT/FT/...),
                        // otherwise the start of the second half would display
                        // e.g. "45+3" and full-time could lose its "90+5".
                        $liveStatus    = $value["fixture"]["status"]["short"];
                        $liveExtra     = $value["fixture"]["status"]["extra"] ?? null;
                        $keepLastExtra = in_array($liveStatus, ['HT', 'BT', 'FT', 'AET', 'PEN'], true);

                        $match->update([
                            'status'     => $liveStatus,
                            'elapsed'    => $value["fixture"]["status"]["elapsed"],
                            'extra'      => $keepLastExtra ? ($liveExtra ?? $match->extra) : $liveExtra,
                            'match_date' => $value["fixture"]["date"],
                        ]);

                    } catch (\Exception $e) {
                        Log::error('live:get fixture failed', [
                            'fixture_id' => $value["fixture"]["id"] ?? null,
                            'error'      => $e->getMessage(),
                        ]);
                    }
                }
            }

        } catch (\Exception $e) {
            Log::error('live:get command failed: ' . $e->getMessage());
        }

        // Sweep ALL of today's fixtures for match-status changes — including
        // FT/AET/PEN, which drop out of the `live=` list before we can catch
        // them. Reuses match:events (date-based, adds sub_type + notificationSent
        // dedup) so "Match Started / Half Time / Match Finished" always arrive.
        try {
            Artisan::call('match:events');
        } catch (\Throwable $e) {
            Log::error('live:get match:events sweep failed: ' . $e->getMessage());
        }

        return 0;
    }
}
