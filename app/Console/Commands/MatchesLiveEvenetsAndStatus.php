<?php

namespace App\Console\Commands;

use App\Models\Matche;
use App\Models\Setting;
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

            if (empty($data)) {
                return 0;
            }

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

                    $this->matchStatus($value, $league, $match, $matches);

                } catch (\Exception $e) {
                    Log::error('live:get fixture failed', [
                        'fixture_id' => $value["fixture"]["id"] ?? null,
                        'error'      => $e->getMessage(),
                    ]);
                }
            }

        } catch (\Exception $e) {
            Log::error('live:get command failed: ' . $e->getMessage());
        }

        return 0;
    }
}
