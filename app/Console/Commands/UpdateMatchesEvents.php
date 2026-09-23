<?php

namespace App\Console\Commands;

use App\Models\Team;
use App\Models\Matche;
use App\Models\Player;
use App\Traits\Notify;
use App\Models\Setting;
use App\Models\testtest;
use App\Models\MatchEvent;
use App\Models\TeamTranslation;
use Illuminate\Console\Command;
use App\Models\PlayerTranslation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Models\MatchEventTranslation;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class UpdateMatchesEvents extends Command
{
    use Notify;

    protected $signature = 'events:update';

    protected $description = 'Get match events every minute and send notifications only for new events';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $setting = Setting::find(1);

        $kontrol = testtest::find(1);
        if ($kontrol->enable != 1) {
            return 0;
        }

        $league = explode(',', $setting->leagues);
        $live   = implode('-', $league);

        $response = Http::withHeaders([
            'x-rapidapi-key' => $setting->football_key,
        ])->get('https://v3.football.api-sports.io/fixtures', [
            'timezone' => 'UTC',
            'live'     => $live,
        ]);

        $data = $response->json('response');

        if (empty($data)) {
            Artisan::call('match:events');
            return 0;
        }

        foreach ($data as $data1) {
            try {
                $match = Matche::where('fixture_id', $data1['fixture']['id'])->first();
                if (!$match) {
                    continue;
                }

                $this->processFixture($setting, $data1, $match);

            } catch (\Exception $e) {
                Log::error('events:update fixture failed', [
                    'fixture_id' => $data1['fixture']['id'] ?? null,
                    'error'      => $e->getMessage(),
                ]);
            }
        }

        // Remove players that have no name translation
        $namedPlayerIds = PlayerTranslation::where('locale', 'en')->pluck('player_id')->toArray();
        Player::whereNotIn('id', $namedPlayerIds)->each(fn($p) => $p->delete());

        Artisan::call('match:events');

        return 0;
    }

    protected function processFixture($setting, array $data1, $match): void
    {
        $matchId   = $match->id;
        $fixtureId = $data1['fixture']['id'];

        $request = Http::withHeaders([
            'x-rapidapi-key' => $setting->football_key,
        ])->get('https://v3.football.api-sports.io/fixtures/events', [
            'fixture' => $fixtureId,
        ]);

        $importantEvents = collect($request->json('response'))
            ->where('type', '!=', 'subst');

        // ── Build a fingerprint set of already-notified events ─────────────
        // Key: "{minute}|{type}|{player_id}" — stored in match_events with
        // event_type = 'AUTO' for events sent, 'PENDING' for events not yet sent.
        // We rely on event_type being nullable for legacy rows.
        $alreadySentFingerprints = MatchEvent::where('match_id', $matchId)
            ->whereNotNull('event_type')       // event_type = 'AUTO' means notification sent
            ->where('event_type', 'AUTO')
            ->get()
            ->map(fn($e) => $e->minute . '|' . $e->status . '|' . $e->player_id)
            ->flip()                            // use as a hash-set for O(1) lookup
            ->toArray();

        // ── Collect all API events that are genuinely new ──────────────────
        $newEvents = [];

        foreach ($importantEvents as $apiEvent) {
            $rawType   = strtolower($apiEvent['type']);
            $rawDetail = strtolower($apiEvent['detail']);

            // Normalise type (mirrors original logic)
            $type = $rawType;
            if ($rawType === 'card')                    $type = $rawDetail;
            if ($rawDetail === 'missed penalty')        $type = $rawDetail;
            if ($rawDetail === 'penalty confirmed')     $type = $rawDetail;
            if ($rawDetail === 'goal cancelled')        $type = $rawDetail;

            // ── Resolve / create team ──────────────────────────────────────
            $team = Team::where('team_id', $apiEvent['team']['id'])->first();
            if (!$team) {
                $team = Team::create([
                    'team_id' => $apiEvent['team']['id'],
                    'image'   => $apiEvent['team']['logo'],
                ]);
                TeamTranslation::create(['name' => $apiEvent['team']['name'], 'team_id' => $team->id, 'locale' => 'en']);
                TeamTranslation::create(['name' => $apiEvent['team']['name'], 'team_id' => $team->id, 'locale' => 'ar']);
            }
            $teamId = $team->id;

            // ── Resolve / create player ────────────────────────────────────
            $player = Player::where('player_id', $apiEvent['player']['id'])->first();
            if (!$player) {
                $playersResponse = Http::withHeaders([
                    'x-rapidapi-key' => $setting->football_key,
                ])->get('https://v3.football.api-sports.io/players', [
                    'id'     => $apiEvent['player']['id'],
                    'season' => $setting->season,
                ])->json('response');

                $pData  = $playersResponse[0] ?? null;
                $fname  = $pData['player']['firstname'] ?? 'Unknown';
                $lname  = $pData['player']['lastname']  ?? '';
                $pos    = $pData['statistics'][0]['games']['position'] ?? null;

                $player = Player::create([
                    'en'               => ['first_name' => $fname, 'last_name' => $lname],
                    'ar'               => ['first_name' => $fname, 'last_name' => $lname],
                    'player_id'        => $apiEvent['player']['id'],
                    'position'         => $pos,
                    'team_id'          => $teamId,
                    'image'            => 'https://media.api-sports.io/football/players/' . $apiEvent['player']['id'] . '.png',
                    'football_team_id' => $apiEvent['team']['id'],
                ]);
            }

            $playerId     = $player->id;
            $playerNameEn = trim($player->translate('en')->first_name . ' ' . $player->translate('en')->last_name);
            $playerNameAr = trim($player->translate('ar')->first_name . ' ' . $player->translate('ar')->last_name);

            $minute      = $apiEvent['time']['elapsed'];
            $fingerprint = $minute . '|' . $type . '|' . $playerId;

            // ── Upsert the event row (keeps DB in sync without deleting) ───
            $existingEvent = MatchEvent::where('match_id', $matchId)
                ->where('minute', $minute)
                ->where('status', $type)
                ->where('player_id', $playerId)
                ->first();

            if (!$existingEvent) {
                $matchEvent = MatchEvent::create([
                    'minute'      => $minute,
                    'status'      => $type,
                    'player_name' => $playerNameEn,
                    'player_id'   => $playerId,
                    'team_id'     => $teamId,
                    'match_id'    => $matchId,
                    // event_type is NULL = not yet notified
                ]);

                MatchEventTranslation::create(['match_event_id' => $matchEvent->id, 'description' => $type, 'locale' => 'en']);
                MatchEventTranslation::create(['match_event_id' => $matchEvent->id, 'description' => $type, 'locale' => 'ar']);

                // Only notify if we have not already sent this fingerprint
                if (!isset($alreadySentFingerprints[$fingerprint])) {
                    $newEvents[] = [
                        'event'         => $apiEvent,
                        'type'          => $type,
                        'rawDetail'     => $rawDetail,
                        'playerNameEn'  => $playerNameEn,
                        'playerNameAr'  => $playerNameAr,
                        'teamId'        => $teamId,
                        'matchEventId'  => $matchEvent->id,
                        'fingerprint'   => $fingerprint,
                    ];
                }
            }
        }

        if (empty($newEvents)) {
            return;
        }

        // ── Only the NEWEST event triggers a push (same behaviour as before) ──
        // If multiple new events arrived in the same poll window, notify for each.
        // Load match/goal context once for this fixture.
        $matches = Matche::where('fixture_id', $fixtureId)
            ->with('competition.parent')
            ->get()
            ->each(function ($builder) {
                $builder->home_team_name_ar = $builder->team1()->first()->translate('ar')->name;
                $builder->home_team_name_en = $builder->team1()->first()->translate('en')->name;
                $builder->away_team_name_ar = $builder->team2()->first()->translate('ar')->name;
                $builder->away_team_name_en = $builder->team2()->first()->translate('en')->name;
                $builder->home_goals        = $builder->events()->getQuery()->where('status', 'goal')->where('team_id', $builder->team1_id)->count();
                $builder->away_goals        = $builder->events()->getQuery()->where('status', 'goal')->where('team_id', $builder->team2_id)->count();
            });

        foreach ($newEvents as $new) {
            $this->sendEventNotification($matches, $new);

            // Mark as sent so re-runs never re-fire it
            MatchEvent::find($new['matchEventId'])?->update(['event_type' => 'AUTO']);
        }
    }

    protected function sendEventNotification($matches, array $new): void
    {
        $apiEvent      = $new['event'];
        $type          = $new['type'];
        $rawDetail     = $new['rawDetail'];
        $playerNameEn  = $new['playerNameEn'];
        $playerNameAr  = $new['playerNameAr'];
        $elapsed       = $apiEvent['time']['elapsed'];
        $teamId        = $new['teamId'];
        $teamNotif     = Team::find($teamId);

        $homeGoals = $matches[0]['home_goals'];
        $awayGoals = $matches[0]['away_goals'];
        $score     = $homeGoals . '-' . $awayGoals;
        $scoreAr   = $awayGoals . '-' . $homeGoals;
        $prefix    = "\n\r '" . $elapsed . '   ';

        // ── Build notification bodies ──────────────────────────────────────
        $NotificationBodyEn = '';
        $NotificationBodyAr = '';

        $hidePlayer = ($playerNameEn === 'Isaac Lihadji');

        switch ($type) {
            case 'yellow card':
                $NotificationBodyEn = $hidePlayer
                    ? $score   . $prefix . 'One player from ' . $teamNotif->translate('en')->name . ' team took yellow card'
                    : $score   . $prefix . $playerNameEn . ' took yellow card';
                $NotificationBodyAr = $hidePlayer
                    ? $scoreAr . $prefix . 'حصل لاعب من فريق ' . $teamNotif->translate('ar')->name . ' على كرت أصفر'
                    : $scoreAr . $prefix . 'حصل اللاعب ' . $playerNameAr . ' على كرت أصفر';
                break;

            case 'second yellow card':
                $NotificationBodyEn = $hidePlayer
                    ? $score   . $prefix . 'One player from ' . $teamNotif->translate('en')->name . ' team took second yellow card'
                    : $score   . $prefix . $playerNameEn . ' took second yellow card';
                $NotificationBodyAr = $hidePlayer
                    ? $scoreAr . $prefix . 'حصل لاعب من فريق ' . $teamNotif->translate('ar')->name . ' على 2 كرت أصفر'
                    : $scoreAr . $prefix . 'حصل اللاعب ' . $playerNameAr . ' على 2 كرت أصفر';
                break;

            case 'red card':
                $NotificationBodyEn = $hidePlayer
                    ? $score   . $prefix . 'One player from ' . $teamNotif->translate('en')->name . ' team took red card'
                    : $score   . $prefix . $playerNameEn . ' took red card';
                $NotificationBodyAr = $hidePlayer
                    ? $scoreAr . $prefix . 'حصل لاعب من فريق ' . $teamNotif->translate('ar')->name . ' على كرت احمر'
                    : $scoreAr . $prefix . 'حصل اللاعب ' . $playerNameAr . ' على كرت أحمر';
                break;

            case 'goal':
                if ($rawDetail === 'penalty') {
                    $NotificationBodyEn = $hidePlayer
                        ? $score   . $prefix . 'One player from ' . $teamNotif->translate('en')->name . ' team scored goal with penalty'
                        : $score   . $prefix . $playerNameEn . ' scored goal with penalty for ' . $teamNotif->translate('en')->name . ' team';
                    $NotificationBodyAr = $hidePlayer
                        ? $scoreAr . $prefix . 'سجل لاعب من فريق ' . $teamNotif->translate('ar')->name . ' هدفا بضربة جزاء'
                        : $scoreAr . $prefix . 'سجل اللاعب ' . $playerNameAr . ' هدفا بضربة جزاء لصالح فريق ' . $teamNotif->translate('ar')->name;
                } elseif ($rawDetail === 'missed penalty') {
                    $NotificationBodyEn = $score   . $prefix . 'Missed penalty for ' . $teamNotif->translate('en')->name . ' team';
                    $NotificationBodyAr = $scoreAr . $prefix . 'ضربة جزاء ضائعة ' . $teamNotif->translate('ar')->name;
                } else {
                    $NotificationBodyEn = $hidePlayer
                        ? $score   . $prefix . 'One player from ' . $teamNotif->translate('en')->name . ' team scored goal'
                        : $score   . $prefix . $playerNameEn . ' scored goal for ' . $teamNotif->translate('en')->name . ' team';
                    $NotificationBodyAr = $hidePlayer
                        ? $scoreAr . $prefix . 'سجل لاعب من فريق ' . $teamNotif->translate('ar')->name . ' هدفا'
                        : $scoreAr . $prefix . 'سجل اللاعب ' . $playerNameAr . ' هدفا لصالح فريق ' . $teamNotif->translate('ar')->name;
                }
                break;

            case 'missed penalty':
                $NotificationBodyEn = $score   . $prefix . 'Missed penalty for ' . $teamNotif->translate('en')->name . ' team';
                $NotificationBodyAr = $scoreAr . $prefix . 'ضربة جزاء ضائعة ' . $teamNotif->translate('ar')->name;
                break;

            case 'penalty confirmed':
                $NotificationBodyEn = $score   . $prefix . 'Penalty given for ' . $teamNotif->translate('en')->name . ' team';
                $NotificationBodyAr = $scoreAr . $prefix . 'ضربة جزاء لصالح فريق ' . $teamNotif->translate('ar')->name;
                break;

            case 'goal cancelled':
                $NotificationBodyEn = $score   . $prefix . 'Goal Cancelled';
                $NotificationBodyAr = $scoreAr . $prefix . 'تم الغاء الهدف';
                break;

            case 'var':
                if ($rawDetail === 'goal cancelled') {
                    $NotificationBodyEn = $score   . $prefix . 'Goal Cancelled';
                    $NotificationBodyAr = $scoreAr . $prefix . 'تم الغاء الهدف';
                } elseif ($rawDetail === 'penalty confirmed') {
                    $NotificationBodyEn = $score   . $prefix . 'The Penalty is confirmed for ' . $teamNotif->translate('en')->name . ' team';
                    $NotificationBodyAr = $scoreAr . $prefix . 'تم احتساب ضربة الجزاء لفريق ' . $teamNotif->translate('ar')->name;
                }
                break;

            default:
                // Unknown event type — skip sending
                return;
        }

        if ($NotificationBodyEn === '') {
            return;
        }

        // ── Collect all team followers, deduplicating across both teams ────
        $teamIds = $matches->pluck('team1_id')->merge($matches->pluck('team2_id'))->unique();

        $notifyPayload = [
            'match_id'   => (string) $matches[0]->id,
            'fixture_id' => (string) ($matches[0]->fixture_id ?? ''),
            'team_id'    => (string) $teamId,
        ];

        $clientsNotified = [];

        foreach ($teamIds as $tid) {
            $client_ids = DB::table('favourite_team')
                ->where('team_id', $tid)
                ->pluck('client_id')
                ->toArray();

            $notYet          = array_diff($client_ids, $clientsNotified);
            $clientsNotified = array_merge($clientsNotified, $notYet);

            if (empty($notYet)) {
                continue;
            }

            $tokens_en = DB::table('clients')
                ->where('locale', 'en')
                ->whereIn('id', $notYet)
                ->whereNotNull('fb_token')
                ->pluck('fb_token');

            $tokens_ar = DB::table('clients')
                ->where('locale', 'ar')
                ->whereIn('id', $notYet)
                ->whereNotNull('fb_token')
                ->pluck('fb_token');

            $getTeam = Team::find($tid);
            if (!$getTeam) {
                continue;
            }

            if ($tokens_en->isNotEmpty()) {
                $this->topicNotifyByFirebaseTokens($tokens_en->toArray(), [
                    'title'  => $matches[0]['home_team_name_en'] . '-' . $matches[0]['away_team_name_en'],
                    'body'   => $NotificationBodyEn,
                    'image'  => $getTeam->image_path,
                    'notify' => $notifyPayload,
                ]);
            }

            if ($tokens_ar->isNotEmpty()) {
                $this->topicNotifyByFirebaseTokens($tokens_ar->toArray(), [
                    'title'  => $matches[0]['home_team_name_ar'] . '-' . $matches[0]['away_team_name_ar'],
                    'body'   => $NotificationBodyAr,
                    'image'  => $getTeam->image_path,
                    'notify' => $notifyPayload,
                ]);
            }
        }
    }
}