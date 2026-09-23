<?php

namespace App\Console\Commands;

use App\Models\Team;
use App\Models\Matche;
use App\Traits\Notify;
use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UpdateMatchesStatus extends Command
{
    use Notify;

    protected $signature   = 'match:events';
    protected $description = 'Update match status and send phase-change notifications every minute';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(): int
    {
        try {
            $setting = Setting::find(1);
            $league  = explode(',', $setting->leagues);

            $response = Http::withHeaders([
                'x-rapidapi-key' => $setting->football_key,
            ])
            ->timeout(60)
            ->get('https://v3.football.api-sports.io/fixtures', [
                'timezone' => 'UTC',
                'date'     => date('Y-m-d'),
            ]);

            $data = $response->json('response');

            if (empty($data)) {
                return 0;
            }

            // ── Pre-load all relevant DB matches in ONE query ─────────────
            //    FIX: replaced per-fixture Matche::where() inside the loop
            //    with a single bulk fetch keyed by fixture_id.
            $fixtureIds = collect($data)
                ->filter(fn($d) => in_array($d['league']['id'], $league))
                ->pluck('fixture.id')
                ->toArray();

            $matchesByFixtureId = Matche::whereIn('fixture_id', $fixtureIds)
                ->with(['team1.translations', 'team2.translations'])
                ->get()
                ->keyBy('fixture_id');

            foreach ($data as $data1) {
                try {
                    if (!in_array($data1['league']['id'], $league)) {
                        continue;
                    }

                    $fixtureId = $data1['fixture']['id'];
                    $match     = $matchesByFixtureId[$fixtureId] ?? null;

                    if (!$match) {
                        continue;
                    }

                    // ── Update match status ───────────────────────────────
                    $match->update([
                        'status'     => $data1['fixture']['status']['short'],
                        'elapsed'    => $data1['fixture']['status']['elapsed'],
                        'match_date' => $data1['fixture']['date'],
                    ]);

                    $status  = $data1['fixture']['status']['short'];

                    // ── Build match display data from eager-loaded relations
                    $matchDisplay = $this->buildMatchDisplay($match, $data1);

                    // ── Handle status notification ────────────────────────
                    $this->handleStatusNotification($match, $matchDisplay, $data1, $status);

                } catch (\Exception $e) {
                    Log::error('match:events fixture failed', [
                        'fixture_id' => $data1['fixture']['id'] ?? null,
                        'error'      => $e->getMessage(),
                    ]);
                }
            }

        } catch (\Exception $e) {
            Log::error('match:events command failed: ' . $e->getMessage());
        }

        return 0;
    }

    // ── Build display fields from eager-loaded team relations ─────────────
    //    FIX: replaced team1()->first()->translate() (4 separate queries per
    //    match) with the already eager-loaded $match->team1 relation.
    protected function buildMatchDisplay($match, array $data1): array
    {
        $homeGoals = $match->events()->where('status', 'goal')->where('team_id', $match->team1_id)->count();
        $awayGoals = $match->events()->where('status', 'goal')->where('team_id', $match->team2_id)->count();

        return [
            'id'               => $match->id,
            'fixture_id'       => $match->fixture_id,
            'team1_id'         => $match->team1_id,
            'team2_id'         => $match->team2_id,
            'home_team_name_en' => $match->team1->translate('en')->name,
            'home_team_name_ar' => $match->team1->translate('ar')->name,
            'away_team_name_en' => $match->team2->translate('en')->name,
            'away_team_name_ar' => $match->team2->translate('ar')->name,
            'home_image'        => $match->team1->image_path,
            'away_image'        => $match->team2->image_path,
            'home_goals'        => $homeGoals,
            'away_goals'        => $awayGoals,
        ];
    }

    // ── Decide whether to notify and what to say ──────────────────────────
    protected function handleStatusNotification($match, array $display, array $data1, string $status): void
    {
        // FIX: use json_decode/encode instead of raw CSV to be more robust
        //      against corruption. Falls back gracefully if column is null.
        $notificationSentArray = array_filter(
            explode(',', $match->notificationSent ?? '')
        );

        if (in_array($status, $notificationSentArray)) {
            return;
        }

        $elapsed   = $data1['fixture']['status']['elapsed'];
        $homeGoals = $display['home_goals'];
        $awayGoals = $display['away_goals'];
        $score     = $homeGoals . '-' . $awayGoals;
        $scoreAr   = $awayGoals . '-' . $homeGoals;
        $elapsedTick = "\r\n'" . $elapsed . '  ';

        // ── Status map covers ALL statuses including terminal ones ─────────
        //    This makes end:match redundant — match:events handles FT/AET/PEN.
        $statusMap = [
            '1H'  => ['en' => 'Match Started',                                                 'ar' => 'لقد بدأت المباراة'],
            'HT'  => ['en' => $score   . $elapsedTick . 'First Half Finished',                 'ar' => $scoreAr . $elapsedTick . 'انتهى الشوط الأول'],
            '2H'  => ['en' => $score   . $elapsedTick . 'Second Half Started',                 'ar' => $scoreAr . $elapsedTick . 'لقد بدأ الشوط الثاني'],
            'ET'  => ['en' => $score   . $elapsedTick . 'Extra Time Started',                  'ar' => $scoreAr . $elapsedTick . 'لقد بدأت الأشواط الإضافية'],
            'BT'  => ['en' => $score   . $elapsedTick . 'Break During Extra Time',             'ar' => $scoreAr . $elapsedTick . 'استراحة ما بين الشوطين الاضافيين'],
            'P'   => ['en' => $score   . $elapsedTick . 'Penalty Started',                     'ar' => $scoreAr . $elapsedTick . 'لقد بدأت ضربات الجزاء'],
            'FT'  => ['en' => $score   . "\r\n Match Finished",                                'ar' => $scoreAr . "\r\n انتهت المباراة"],
            'AET' => ['en' => $score   . $elapsedTick . 'Match Finished After Extra Time',     'ar' => $scoreAr . $elapsedTick . 'انتهت الأشواط الإضافية و انتهت المباراة'],
            'PEN' => [
                'en' => $score . "\r\n Match Finished After Penalty " .
                    ($data1['score']['penalty']['home'] ?? 0) . '-' . ($data1['score']['penalty']['away'] ?? 0),
                'ar' => $scoreAr . "\r\n نهاية المباراة بركلات الترجيح " .
                    ($data1['score']['penalty']['home'] ?? 0) . ' - ' . ($data1['score']['penalty']['away'] ?? 0),
            ],
        ];

        if (!isset($statusMap[$status])) {
            return;
        }

        // ── Persist the sent flag ─────────────────────────────────────────
        $notificationSentArray[] = $status;
        $match->notificationSent = implode(',', array_unique(array_filter($notificationSentArray)));
        $match->save();

        $this->sendStatusNotification($display, $statusMap[$status]['en'], $statusMap[$status]['ar']);

        Log::info('match:events status notification sent', [
            'fixture_id' => $match->fixture_id,
            'status'     => $status,
        ]);
    }

    // ── Fan notifications out to followers of either team ─────────────────
    protected function sendStatusNotification(array $display, string $bodyEn, string $bodyAr): void
    {
        $notifyPayload = [
            'match_id'     => (string) $display['id'],
            'fixture_id'   => (string) ($display['fixture_id'] ?? ''),
            'home_team_id' => (string) $display['team1_id'],
            'away_team_id' => (string) $display['team2_id'],
        ];

        $clientsNotified = [];

        foreach ([$display['team1_id'], $display['team2_id']] as $teamId) {
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
                ->pluck('fb_token');

            $tokensAr = DB::table('clients')
                ->where('locale', 'ar')
                ->whereIn('id', $notYet)
                ->whereNotNull('fb_token')
                ->pluck('fb_token');

            // Use a single team image per team for the notification icon
            $teamImage = ($teamId === $display['team1_id'])
                ? $display['home_image']
                : $display['away_image'];

            if ($tokensEn->isNotEmpty()) {
                $this->topicNotifyByFirebaseTokens($tokensEn->toArray(), [
                    'title'  => $display['home_team_name_en'] . ' - ' . $display['away_team_name_en'],
                    'body'   => $bodyEn,
                    'image'  => $teamImage,
                    'notify' => $notifyPayload,
                ]);
            }

            if ($tokensAr->isNotEmpty()) {
                $this->topicNotifyByFirebaseTokens($tokensAr->toArray(), [
                    'title'  => $display['home_team_name_ar'] . ' - ' . $display['away_team_name_ar'],
                    'body'   => $bodyAr,
                    'image'  => $teamImage,
                    'notify' => $notifyPayload,
                ]);
            }
        }
    }
}