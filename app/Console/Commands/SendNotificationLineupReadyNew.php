<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Models\Team;
use App\Models\Matche;
use App\Models\Competition;
use App\Models\Setting;
use App\Traits\Notify;
use App\Traits\NotificationOfMatchesTrait;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendNotificationLineupReadyNew extends Command
{
    use Notify, NotificationOfMatchesTrait;

    protected $signature = 'lineups:new';

    protected $description = 'Send notification when lineup is confirmed available via the API';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(): int
    {
        try {
            $setting = Setting::find(1);
            $league  = explode(',', $setting->leagues);

            // ── 1. Fetch leagues that support lineups ─────────────────────
            $leaguesResponse = Http::withHeaders([
                'x-rapidapi-key' => $setting->football_key,
            ])->timeout(30)->get('https://v3.football.api-sports.io/leagues', [
                'season'  => $setting->season,
                'current' => 'true',
            ]);

            $leaguesData              = $leaguesResponse->json('response') ?? [];
            $leaguesWithLineupsAvailable = [];

            foreach ($leaguesData as $value) {
                foreach ($value['seasons'] as $season) {
                    if (
                        ($season['coverage']['fixtures']['lineups'] ?? false) === true &&
                        in_array($value['league']['id'], $league)
                    ) {
                        $leaguesWithLineupsAvailable[] = $value['league']['id'];
                    }
                }
            }

            if (empty($leaguesWithLineupsAvailable)) {
                Log::info('lineups:new — no lineup-supporting leagues found');
                return 0;
            }

            // ── 2. Competitions that support lineups ──────────────────────
            $competitionIds = Competition::whereIn('league_id', $leaguesWithLineupsAvailable)
                ->pluck('id');

            // ── 3. Matches in the next 60 minutes (lineup window) ─────────
            //    FIX: competition_id filter restored (was commented out).
            //    FIX: time window added — lineups typically published ≤60 min
            //         before kick-off, so no need to scan the whole day.
            $matches = Matche::query()
                ->whereIn('competition_id', $competitionIds)
                ->whereDate('match_date', date('Y-m-d'))
                ->where('match_date', '>', Carbon::now())
                ->where('match_date', '<=', Carbon::now()->addMinutes(60))
                ->get();

            if ($matches->isEmpty()) {
                return 0;
            }

            foreach ($matches as $match) {
                try {
                    $this->processMatch($setting, $match);
                } catch (\Exception $e) {
                    Log::error('lineups:new match failed', [
                        'match_id' => $match->id,
                        'error'    => $e->getMessage(),
                    ]);
                }
            }

        } catch (\Exception $e) {
            Log::error('lineups:new command failed: ' . $e->getMessage());
        }

        return 0;
    }

    protected function processMatch($setting, $match): void
    {
        // ── FIX: dedup key uses both team IDs so a match is only processed
        //    once, not once-per-team as the original erroneously did ─────────
        if ($this->checkEventSent(0, 'lineup', $match->team1_id, $match->id) === false) {
            return;
        }
        if ($this->checkEventSent(0, 'lineup', $match->team2_id, $match->id) === false) {
            return;
        }

        // ── FIX: actually call the API to verify lineups are published ────
        $response = Http::withHeaders([
            'x-rapidapi-key' => $setting->football_key,
        ])->timeout(30)->get('https://v3.football.api-sports.io/fixtures/lineups', [
            'fixture' => $match->fixture_id,
        ]);

        $data = $response->json();

        // Only proceed when both teams' lineups are confirmed (results == 2)
        if (($data['results'] ?? 0) !== 2) {
            return;
        }

        // ── Mark as sent before firing to prevent double-send ─────────────
        $homeTeam = Team::find($match->team1_id);
        $awayTeam = Team::find($match->team2_id);

        if (!$homeTeam || !$awayTeam) {
            Log::warning('lineups:new — team not found', ['match_id' => $match->id]);
            return;
        }

        $this->storeEvent(0, 'lineup', null, null, $homeTeam, $match->id);
        $this->storeEvent(0, 'lineup', null, null, $awayTeam, $match->id);

        // ── Build match context array used by sendNotificationsToUsers ────
        $matchArray = $this->matchArrayFromFixtureId($match->fixture_id, 0, 0);

        $this->sendNotificationsToUsers(
            $matchArray,
            'The lineup for both teams is available now',
            'تشكيلة الفريقين متاحة الان',
            false,
            true
        );

        Log::info('lineups:new sent', ['fixture_id' => $match->fixture_id]);
    }
}