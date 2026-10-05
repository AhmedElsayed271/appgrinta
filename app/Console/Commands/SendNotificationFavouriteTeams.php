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

    protected $signature = 'notification:favourite_team
                            {--dry-run : Run without sending notifications}
                            {--date= : Simulate a specific date (Y-m-d)}
                            {--hour=10 : Simulate a specific hour (0-23)}';

    protected $description = 'Send "your favourite team plays today" notification at 10:00 in each client\'s local timezone';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $isDryRun     = (bool) $this->option('dry-run');
        $simulateDate = $this->option('date');
        $simulateHour = (int) $this->option('hour');

        if ($isDryRun) {
            $this->info('DRY RUN MODE — no notifications will be sent');
        }

        $timezones = Client::cachedTimezones();
        $totalSent = 0;

        foreach ($timezones as $timezone) {
            try {
                $localNow = $simulateDate
                    ? Carbon::parse($simulateDate . ' ' . $simulateHour . ':00:00', $timezone)
                    : Carbon::now($timezone);
            } catch (\Exception $e) {
                Log::warning('notification:favourite_team invalid timezone', ['timezone' => $timezone]);
                continue;
            }

            // Fire any minute in the 10:00–10:09 window. A per-team/day cache
            // (set only after a successful send) guarantees exactly one
            // notification per day and lets a missed/failed run retry.
            if (!$simulateDate) {
                if ($localNow->hour !== 10 || $localNow->minute > 9) {
                    continue;
                }
            }

            $localToday = $localNow->format('Y-m-d');

            // Convert the client's local day to a UTC window so matches stored
            // in UTC are matched against the correct local date.
            $startTodayUtc = $localNow->copy()->startOfDay()->utc()->format('Y-m-d H:i:s');
            $endTodayUtc   = $localNow->copy()->endOfDay()->utc()->format('Y-m-d H:i:s');

            // ── Load today's matches once per timezone ────────────────────
            //    Eager-load team translations to avoid N+1 in the notify loop.
            $matches = Matche::query()
                ->where('match_date', '>=', $startTodayUtc)
                ->where('match_date', '<=', $endTodayUtc)
                ->with(['team1.translations', 'team2.translations'])
                ->get();

            if ($matches->isEmpty()) {
                continue;
            }

            // ── Build a lookup: team_id → match — done once, O(1) later ───
            $matchByTeamId = [];
            foreach ($matches as $match) {
                if ($match->team1_id) {
                    $matchByTeamId[$match->team1_id] = $match;
                }
                if ($match->team2_id) {
                    $matchByTeamId[$match->team2_id] = $match;
                }
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

                // Dedup: once per (timezone, team, day), recorded after send.
                $notifiedKey = 'favourite_team_notified_' . $timezone . '_' . $teamId . '_' . $localToday;
                if (!$isDryRun && cache()->has($notifiedKey)) {
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

                if ($isDryRun) {
                    if ($tokensEn->isNotEmpty() || $tokensAr->isNotEmpty()) {
                        $this->line('WOULD SEND: ' . $getTeam->translate('en')->name
                            . ' [' . $timezone . ']'
                            . ' | EN: ' . $tokensEn->count()
                            . ' | AR: ' . $tokensAr->count()
                            . ' | match_id: ' . $match->id);
                        $totalSent++;
                    }
                    continue;
                }

                $sentEn = $tokensEn->isNotEmpty();
                $sentAr = $tokensAr->isNotEmpty();

                if ($sentEn) {
                    $this->topicNotifyByFirebaseTokens($tokensEn->toArray(), [
                        'title'  => $getTeam->translate('en')->name,
                        'body'   => 'Your favourite team ' . $getTeam->translate('en')->name . ' has a match today',
                        'image'  => $getTeam->image_path,
                        'notify' => $notifyPayload,
                    ]);
                }

                if ($sentAr) {
                    $this->topicNotifyByFirebaseTokens($tokensAr->toArray(), [
                        'title'  => $getTeam->translate('ar')->name,
                        'body'   => 'فريقك المفضل ' . $getTeam->translate('ar')->name . ' لديه مباراة اليوم',
                        'image'  => $getTeam->image_path,
                        'notify' => $notifyPayload,
                    ]);
                }

                if ($sentEn || $sentAr) {
                    cache()->put($notifiedKey, true, now()->addDays(2));
                    Log::info('favourite_team sent', [
                        'team_id'  => $teamId,
                        'timezone' => $timezone,
                        'tokens'   => $tokensEn->count() + $tokensAr->count(),
                    ]);
                    $totalSent++;
                }
            }
        }

        $this->info('Finished favourite_team — sent: ' . $totalSent);

        return 0;
    }
}
