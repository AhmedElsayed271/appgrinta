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

                        // Keep the running half's stoppage time, but never let
                        // it carry into a new period (2H/ET/P); otherwise the
                        // start of the second half would display e.g. "45+3".
                        $liveStatus    = $value["fixture"]["status"]["short"];
                        $isPeriodStart = in_array($liveStatus, ['1H', '2H', 'ET', 'P'], true);

                        $match->update([
                            'status'     => $liveStatus,
                            'elapsed'    => $value["fixture"]["status"]["elapsed"],
                            'extra'      => $isPeriodStart
                                ? null
                                : ($value["fixture"]["status"]["extra"] ?? $match->extra),
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
