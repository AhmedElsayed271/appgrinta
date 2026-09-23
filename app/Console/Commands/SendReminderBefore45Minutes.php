<?php

namespace App\Console\Commands;

use App\Models\Matche;
use Illuminate\Console\Command;
use App\Traits\Notify;
use Carbon\Carbon;
use App\Models\Team;
use App\Http\Resources\MatchResource;
use App\Traits\NotificationOfMatchesTrait;
use Illuminate\Support\Facades\DB;
use App\Models\Client;
use Illuminate\Support\Facades\Log;

class SendReminderBefore45Minutes extends Command
{
    use Notify , NotificationOfMatchesTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:reminder';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send reminder to user before 45 minute of match when his favourite team will play match';

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
            // ── 1. Fetch today's matches without time restriction ─────────
            $upcomingMatches = Matche::with(['competition.parent', 'team1.translations', 'team2.translations'])
                ->whereNotNull('fixture_id')
                ->whereDate('match_date', Carbon::today())
                ->whereNotIn('status', ['PST', 'CANC', 'SUSP', 'ABD', 'AWD', 'WO'])
                // ->where('match_date', '>', Carbon::now()) // not started yet
                ->get();

            $this->processMatchesForAllClients($upcomingMatches);

            // ── 2. Matches without fixture_id ─────────────────────────────
            $upcomingMatchesNoFixture = Matche::with(['competition.parent', 'team1.translations', 'team2.translations'])
                ->whereNull('fixture_id')
                ->whereDate('match_date', Carbon::today())
                ->whereNotIn('status', ['PST', 'CANC', 'SUSP', 'ABD', 'AWD', 'WO'])
                ->where('match_date', '>', Carbon::now())
                ->get();

            $this->processMatchesForAllClients($upcomingMatchesNoFixture);

        } catch (\Exception $e) {
            Log::error('Match notification job failed: ' . $e->getMessage());
        }
    }

    protected function processMatchesForAllClients($matches)
    {
        // Group clients by timezone to minimize timezone conversions (cached)
        $clientsByTimezone = Client::cachedClientsGroupedByTimezone();

        foreach ($clientsByTimezone as $timezone => $clients) {
            $this->processMatchesForTimezone($matches, $clients, $timezone);
        }
    }

    protected function processMatchesForTimezone($matches, $clients, $timezone)
    {
        $nowInClientTimezone = Carbon::now($timezone);

        foreach ($matches as $match) {
            // ── Convert match UTC time to client timezone ─────────────────
            $matchTimeInClientTz = Carbon::parse($match->match_date)
                ->utc()
                ->setTimezone($timezone);

            // ── Check if 45 minutes remain ────────────────────────────────
            $minutesUntilMatch = $nowInClientTimezone->diffInMinutes($matchTimeInClientTz, false);

            // Allow small tolerance because cron may not run exactly on time
            if ($minutesUntilMatch < 44 || $minutesUntilMatch > 46) {
                continue;
            }
            
            // ── Deduplication ─────────────────────────────────────────────
            if ($this->checkEventSent(0, 'reminder-45', $match->team1_id, $match->id) == false) {
                continue;
            }
            if ($this->checkEventSent(0, 'reminder-45', $match->team2_id, $match->id) == false) {
                continue;
            }

            // ── Store before sending ──────────────────────────────────────
            $homeTeam = Team::find($match->team1_id);
            $awayTeam = Team::find($match->team2_id);
            $this->storeEvent(0, 'reminder-45', null, null, $homeTeam, $match->id);
            $this->storeEvent(0, 'reminder-45', null, null, $awayTeam, $match->id);

            $this->sendNotificationsToClients($match, $clients);
        }
    }

    protected function sendNotificationsToClients($match)
    {
        $match  = $this->prepareMatchData($match);
        $teamIds = [$match->team1_id, $match->team2_id];

        $clientIds = DB::table('favourite_team')
            ->whereIn('team_id', $teamIds)
            ->pluck('client_id')
            ->unique()
            ->toArray();

        if (empty($clientIds)) return;

        $notifyPayload = [
            'match_id'     => (string) $match->id,
            'fixture_id'   => (string) ($match->fixture_id ?? ''),
            'home_team_id' => (string) $match->team1_id,
            'away_team_id' => (string) $match->team2_id,
        ];

        $englishClients = DB::table('clients')
            ->where('locale', 'en')
            ->whereIn('id', $clientIds)
            ->whereNotNull('fb_token')
            ->pluck('fb_token');

        $arabicClients = DB::table('clients')
            ->where('locale', 'ar')
            ->whereIn('id', $clientIds)
            ->whereNotNull('fb_token')
            ->pluck('fb_token');

        if ($englishClients->isNotEmpty()) {
            $this->topicNotifyByFirebaseTokens($englishClients->toArray(), [
                'title'  => "{$match->home_team_name_en} vs {$match->away_team_name_en}",
                'body'   => __('site.notification.match_starting_after_45_min', [], 'en'),
                'image'  => $this->getMergedImage($match->home_image, $match->away_image),
                'notify' => $notifyPayload,
            ]);
        }

        if ($arabicClients->isNotEmpty()) {
            $this->topicNotifyByFirebaseTokens($arabicClients->toArray(), [
                'title'  => "{$match->home_team_name_ar} ضد {$match->away_team_name_ar}",
                'body'   => __('site.notification.match_starting_after_45_min', [], 'ar'),
                'image'  => $this->getMergedImage($match->home_image, $match->away_image),
                'notify' => $notifyPayload,
            ]);
        }
    }

    protected function prepareMatchData($match)
    {
        $match->home_team_name_ar = $match->team1->getTranslation('ar')->name ?? $match->team1->name;
        $match->home_team_name_en = $match->team1->getTranslation('en')->name ?? $match->team1->name;
        $match->away_team_name_ar = $match->team2->getTranslation('ar')->name ?? $match->team2->name;
        $match->away_team_name_en = $match->team2->getTranslation('en')->name ?? $match->team2->name;
        $match->home_image = $match->team1->image_path;
        $match->away_image = $match->team2->image_path;

        return $match;
    }

    protected function getMergedImage($homeImage, $awayImage)
    {
        try {
            return $this->mergeImagesInOneImage($homeImage, $awayImage);
        } catch (\Exception $e) {
            Log::warning("Image merge failed: " . $e->getMessage());
            return $homeImage; // Fallback to home image
        }
    }

}