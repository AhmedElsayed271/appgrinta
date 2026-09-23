<?php

namespace App\Console\Commands;

use App\Models\Matche;
use App\Models\Setting;
use App\Traits\NotificationOfMatchesTrait;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EndMatchEventCommand extends Command
{
     use NotificationOfMatchesTrait;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'end:match';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a notification for the end of a match';

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
    public function handle(): int
    {
        try {
            $setting = Setting::find(1);
            $league  = explode(",", $setting->leagues);

            $response = Http::withHeaders([
                'x-rapidapi-key' => $setting->football_key
            ])
            ->timeout(60)
            ->get('https://v3.football.api-sports.io/fixtures', [
                'timezone' => 'UTC',
                'date'     => date("Y-m-d"),
            ]);

            $data = $response->json("response");

            if (empty($data)) {
                return 0;
            }

            foreach ($data as $value) {
                try {
                    // ── Skip leagues not in our list ───────────────────────
                    if (!in_array($value["league"]["id"], $league)) {
                        continue;  // ← was return false
                    }

                    $match = Matche::where('fixture_id', $value["fixture"]["id"])->first();

                    if (!$match) {
                        continue;
                    }

                    $matches = $this->matchArrayFromFixtureId(
                        $value["fixture"]["id"],
                        $value['goals']['home'],
                        $value['goals']['away']
                    );

                    $match->update([
                        'status'     => $value["fixture"]["status"]["short"],
                        'elapsed'    => $value["fixture"]["status"]["elapsed"],
                        'match_date' => $value["fixture"]["date"],
                    ]);

                    $status                    = $value["fixture"]["status"]["short"];
                    $notificationSentDatabase  = $match->notificationSent;
                    $notificationSentDatabaseArray = explode(',', $notificationSentDatabase);

                    if ($status == "FT") {
                        $this->sendMatchStatus($match, $matches, "FT", $notificationSentDatabaseArray, "نهاية المباراة", "Match Finished", true, true);
                    } elseif ($status == "BT") {
                        $this->sendMatchStatus($match, $matches, "BT", $notificationSentDatabaseArray, "استراحة ما بين الأشواط الإضافية", "Break During Extra Time", true, true);
                    } elseif ($status == "AET") {
                        $this->sendMatchStatus($match, $matches, "AET", $notificationSentDatabaseArray, "نهاية الأشواط الإضافية و انتهت المباراة", "Match Finished After Extra Time", true, true);
                    } elseif ($status == "PEN") {
                        $this->sendMatchStatus(
                            $match, $matches, "PEN", $notificationSentDatabaseArray,
                            "نهاية المباراة بركلات الترجيح " . ($value['score']['penalty']['home'] ?? 0) . ' - ' . ($value['score']['penalty']['away'] ?? 0),
                            "Match Finished After Penalty " . ($value['score']['penalty']['home'] ?? 0) . ' - ' . ($value['score']['penalty']['away'] ?? 0),
                            false, true
                        );
                    }

                } catch (\Exception $e) {
                    Log::error('end:match fixture failed', [
                        'fixture_id' => $value["fixture"]["id"] ?? null,
                        'error'      => $e->getMessage(),
                    ]);
                }
            }

        } catch (\Exception $e) {
            Log::error('end:match command failed: ' . $e->getMessage());
        }

        return 0;  // ← always return int 0
    }
}
