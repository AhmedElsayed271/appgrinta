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

class SendNotificationLineupReady extends Command
{
    use Notify, NotificationOfMatchesTrait;

    protected $signature = 'lineup:ready';

    protected $description = 'Send notification when lineup is ready (checks matches starting within 15 minutes)';

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

            $leaguesData             = $leaguesResponse->json('response') ?? [];
            $leaguesWithLineups = [];

            foreach ($leaguesData as $value) {
                foreach ($value['seasons'] as $season) {
                    if (
                        ($season['coverage']['fixtures']['lineups'] ?? false) === true &&
                        in_array($value['league']['id'], $league)
                    ) {
                        $leaguesWithLineups[] = $value['league']['id'];
                    }
                }
            }

            if (empty($leaguesWithLineups)) {
                return 0;
            }

            // ── 2. Competitions that support lineups ──────────────────────
            $competitionIds = Competition::whereIn('league_id', $leaguesWithLineups)->pluck('id');

            // ── 3. Matches starting within the next 15 minutes ───────────
            //    FIX: competition_id filter restored (was commented out),
            //    so we no longer scan every match on the day — only relevant ones.
            //    FIX: eager-load team1/team2 translations to avoid N+1 queries
            //    inside the ->each() closure below.
            $matches = Matche::query()
                ->whereIn('competition_id', $competitionIds)
                ->whereDate('match_date', date('Y-m-d'))
                ->where('match_date', '>', Carbon::now())
                ->where('match_date', '<=', Carbon::now()->addMinutes(15))
                ->with(['team1.translations', 'team2.translations', 'competition.parent'])
                ->get()
                ->each(function ($match) {
                    // Use eager-loaded relations instead of calling first() each time
                    $match->home_team_name_en = $match->team1->translate('en')->name;
                    $match->home_team_name_ar = $match->team1->translate('ar')->name;
                    $match->away_team_name_en = $match->team2->translate('en')->name;
                    $match->away_team_name_ar = $match->team2->translate('ar')->name;
                    $match->home_image        = $match->team1->image_path;
                    $match->away_image        = $match->team2->image_path;
                });

            foreach ($matches as $match) {
                try {
                    $this->processMatch($setting, $match);
                } catch (\Exception $e) {
                    Log::error('lineup:ready match failed', [
                        'match_id' => $match->id,
                        'error'    => $e->getMessage(),
                    ]);
                }
            }

        } catch (\Exception $e) {
            Log::error('lineup:ready command failed: ' . $e->getMessage());
        }

        return 0;
    }

    protected function processMatch($setting, $match): void
    {
        // ── Deduplication: skip if already notified for this match ────────
        if ($this->checkEventSent(0, 'lineup-ready', $match->team1_id, $match->id) === false) {
            return;
        }
        if ($this->checkEventSent(0, 'lineup-ready', $match->team2_id, $match->id) === false) {
            return;
        }

        // ── Check lineups actually published for this fixture ─────────────
        $response = Http::withHeaders([
            'x-rapidapi-key' => $setting->football_key,
        ])->timeout(30)->get('https://v3.football.api-sports.io/fixtures/lineups', [
            'fixture' => $match->fixture_id,
        ]);

        $data = $response->json();

        if (($data['results'] ?? 0) !== 2) {
            return;
        }

        // ── Mark sent before firing ───────────────────────────────────────
        $homeTeam = Team::find($match->team1_id);
        $awayTeam = Team::find($match->team2_id);

        $this->storeEvent(0, 'lineup-ready', null, null, $homeTeam, $match->id);
        $this->storeEvent(0, 'lineup-ready', null, null, $awayTeam, $match->id);

        // ── Collect followers of both teams, deduplicating clients ────────
        $clientsNotified = [];

        $notifyPayload = [
            'screen'       => '2',
            'fixture_id'   => (string) ($match->fixture_id ?? ''),
            'match_id'     => (string) $match->id,
            'home_team_id' => (string) $match->team1_id,
            'away_team_id' => (string) $match->team2_id,
            'lineup'       => 'ok',
        ];

        try {
            $mergedImage = $this->mergeImagesInOneImage($match->away_image, $match->home_image);
        } catch (\Exception $e) {
            Log::warning('lineup:ready image merge failed: ' . $e->getMessage());
            $mergedImage = $match->home_image ?? '';
        }

        foreach ([$match->team1_id, $match->team2_id] as $teamId) {
            $followerIds = DB::table('favourite_team')
                ->where('team_id', $teamId)
                ->pluck('client_id')
                ->toArray();

            $notYet          = array_diff($followerIds, $clientsNotified);
            $clientsNotified = array_merge($clientsNotified, $notYet);

            if (empty($notYet)) {
                continue;
            }

            $tokensEn = DB::table('clients')
                ->where('locale', 'en')
                ->whereIn('id', $notYet)
                ->whereNotNull('fb_token')
                ->pluck('fb_token')
                ->toArray();

            $tokensAr = DB::table('clients')
                ->where('locale', 'ar')
                ->whereIn('id', $notYet)
                ->whereNotNull('fb_token')
                ->pluck('fb_token')
                ->toArray();

            if (!empty($tokensEn)) {
                $this->topicNotifyByFirebaseTokens($tokensEn, [
                    'title'  => $match->home_team_name_en . ' - ' . $match->away_team_name_en,
                    'body'   => 'The lineup for both teams is available now',
                    'image'  => $mergedImage,
                    'notify' => $notifyPayload,
                ]);
            }

            if (!empty($tokensAr)) {
                $this->topicNotifyByFirebaseTokens($tokensAr, [
                    'title'  => $match->home_team_name_ar . ' - ' . $match->away_team_name_ar,
                    'body'   => 'تشكيلة الفريقين متاحة الان',
                    'image'  => $mergedImage,
                    'notify' => $notifyPayload,
                ]);
            }
        }

        Log::info('lineup:ready sent', ['fixture_id' => $match->fixture_id]);
    }
}