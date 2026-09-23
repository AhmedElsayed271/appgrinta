<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Models\Team;
use App\Models\Client;
use App\Models\Matche;
use App\Traits\Notify;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SendNotificationFavouriteTeams extends Command
{
    use Notify;

    protected $signature = 'notification:favourite_team';

    // FIX: description corrected to match the actual hour check (10:00, not 08:00)
    protected $description = 'Send "your favourite team plays today" notification at 10:00 in each client\'s local timezone';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $timezones = Client::cachedTimezones();

        foreach ($timezones as $timezone) {
            try {
                $localNow = Carbon::now($timezone);
            } catch (\Exception $e) {
                Log::warning('notification:favourite_team invalid timezone', ['timezone' => $timezone]);
                continue;
            }

            if ($localNow->hour !== 10 || $localNow->minute !== 0) {
                continue;
            }

            $localToday = $localNow->format('Y-m-d');

            // ── Load today's matches once per timezone ────────────────────
            //    FIX: goal counts removed — irrelevant for a morning "plays
            //    today" notification and caused N+1 event queries per match.
            //    FIX: eager-load team translations to avoid N+1 in notify loop.
            $matches = Matche::query()
                ->whereDate('match_date', $localToday)
                ->with(['team1.translations', 'team2.translations'])
                ->get();

            if ($matches->isEmpty()) {
                continue;
            }

            // ── Build a lookup: team_id (API) → match — done once, O(1) later
            $matchByTeamId = [];
            foreach ($matches as $match) {
                $matchByTeamId[$match->team1_id] = $match;
                $matchByTeamId[$match->team2_id] = $match;
            }

            // ── Build team id set (our DB primary keys) ───────────────────
            $teamIds = array_keys($matchByTeamId);

            // ── Load all teams in one query ───────────────────────────────
            $teamsById = Team::whereIn('id', $teamIds)->get()->keyBy('id');

            foreach ($teamIds as $teamId) {
                $match   = $matchByTeamId[$teamId];
                $getTeam = $teamsById[$teamId] ?? null;

                if (!$getTeam) {
                    continue;
                }

                // ── Scope follower tokens to this timezone ────────────────
                $followerIds = DB::table('favourite_team')
                    ->where('team_id', $teamId)
                    ->pluck('client_id')
                    ->toArray();

                if (empty($followerIds)) {
                    continue;
                }

                $tokensEn = DB::table('clients')
                    ->where('locale', 'en')
                    ->where('timezone', $timezone)
                    ->whereIn('id', $followerIds)
                    ->whereNotNull('fb_token')
                    ->pluck('fb_token');

                $tokensAr = DB::table('clients')
                    ->where('locale', 'ar')
                    ->where('timezone', $timezone)
                    ->whereIn('id', $followerIds)
                    ->whereNotNull('fb_token')
                    ->pluck('fb_token');

                $notifyPayload = [
                    'fixture_id' => (string) ($match->fixture_id ?? ''),
                    'match_id'   => (string) $match->id,
                    'team_id'    => (string) $getTeam->team_id,
                ];

                if ($tokensEn->isNotEmpty()) {
                    $this->topicNotifyByFirebaseTokens($tokensEn->toArray(), [
                        'title'  => $getTeam->translate('en')->name,
                        'body'   => 'Your favourite team ' . $getTeam->translate('en')->name . ' has a match today',
                        'image'  => $getTeam->image_path,
                        'notify' => $notifyPayload,
                    ]);
                }

                if ($tokensAr->isNotEmpty()) {
                    $this->topicNotifyByFirebaseTokens($tokensAr->toArray(), [
                        'title'  => $getTeam->translate('ar')->name,
                        'body'   => 'فريقك المفضل ' . $getTeam->translate('ar')->name . ' لديه مباراة اليوم',
                        'image'  => $getTeam->image_path,
                        'notify' => $notifyPayload,
                    ]);
                }
            }
        }

        return 0;
    }
}