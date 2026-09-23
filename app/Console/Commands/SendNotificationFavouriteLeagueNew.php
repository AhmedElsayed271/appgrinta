<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Models\Matche;
use App\Models\Client;
use App\Traits\Notify;
use App\Models\Competition;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Traits\NotificationOfMatchesTrait;

class SendNotificationFavouriteLeagueNew extends Command
{
    use Notify, NotificationOfMatchesTrait;

    /*
        # Dry run with today's date
        php artisan notification:favourite_league --dry-run

        # Dry run with specific date
        php artisan notification:favourite_league --dry-run --date=2026-05-25

        # Dry run with specific date and hour
        php artisan notification:favourite_league --dry-run --date=2026-05-25 --hour=9

        # Real run
        php artisan notification:favourite_league
    */

    protected $signature   = 'notification:favourite_league
                                {--dry-run : Run without sending notifications}
                                {--date= : Simulate a specific date (Y-m-d)}
                                {--hour=9 : Simulate a specific hour (0-23)}';

    protected $description = 'Send "favourite league round starts today" notification at 09:00 in each client\'s local timezone';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $isDryRun     = $this->option('dry-run');
        $simulateDate = $this->option('date');
        $simulateHour = (int) $this->option('hour');

        if ($isDryRun) {
            $this->info('🔍 DRY RUN MODE — no notifications will be sent');
            $this->info('─────────────────────────────────────────────');
        }

        try {
            $finishedStatuses = ['FT', 'AET', 'PEN', 'PST', 'CANC', 'SUSP', 'ABD', 'AWD', 'WO'];

            // ── 1. Pre-collect timezones at 09:00 ──────────────────────────
            $activeTimezones = collect(Client::cachedTimezones())->filter(function ($timezone) use ($simulateDate, $simulateHour, $isDryRun) {
                try {
                    $localNow = $simulateDate
                        ? Carbon::parse($simulateDate . ' ' . $simulateHour . ':00:00', $timezone)
                        : Carbon::now($timezone);

                    // ── In dry-run with date, skip hour check ──────────────
                    if (!$simulateDate) {
                        if ($localNow->hour !== 9 || $localNow->minute > 2) {
                            return false;
                        }
                    }

                    // ── Skip cache check in dry-run ────────────────────────
                    if (!$isDryRun) {
                        $cacheKey = 'favourite_league_notified_' . $timezone . '_' . $localNow->format('Y-m-d');
                        if (cache()->has($cacheKey)) {
                            return false;
                        }
                        cache()->put($cacheKey, true, now()->addDay());
                    }

                    return true;

                } catch (\Exception $e) {
                    return false;
                }
            })->values()->toArray();

            if (empty($activeTimezones)) {
                $this->warn('No timezones at ' . $simulateHour . ':00 right now.');
                return 0;
            }

            $this->info('Active timezones: ' . implode(', ', $activeTimezones));

            // ── 2. Get only competitions that have at least one favourite ──
            $favouritedCompetitionIds = DB::table('favourite_competition')
                ->distinct()
                ->pluck('competition_id')
                ->toArray();

            if (empty($favouritedCompetitionIds)) {
                $this->warn('No favourited competitions found.');
                return 0;
            }

            $competitions = Competition::whereIn('id', $favouritedCompetitionIds)->get();
            $totalSent    = 0;
            $totalSkipped = 0;

            foreach ($competitions as $league) {

                try {
                    $favourites = DB::table('favourite_competition')
                        ->join('clients', 'clients.id', '=', 'favourite_competition.client_id')
                        ->where('favourite_competition.competition_id', $league->id)
                        ->whereNotNull('clients.fb_token')
                        ->whereNotNull('clients.timezone')
                        ->whereIn('clients.timezone', $activeTimezones)
                        ->select('clients.id', 'clients.locale', 'clients.fb_token', 'clients.timezone')
                        ->get();

                    if ($favourites->isEmpty()) {
                        continue;
                    }

                    $clientsByTimezone = $favourites->groupBy('timezone');

                    foreach ($clientsByTimezone as $timezone => $clients) {

                        $localNow = $simulateDate
                            ? Carbon::parse($simulateDate . ' ' . $simulateHour . ':00:00', $timezone)
                            : Carbon::now($timezone);

                        $localToday = $localNow->format('Y-m-d');

                        // ── Today's UTC window for this timezone ───────────────────────
                        //    Convert local midnight→23:59:59 to UTC so the DB query is
                        //    timezone-aware without needing a DB function.
                        $startTodayUtc = $localNow->copy()
                            ->startOfDay()
                            ->utc()
                            ->format('Y-m-d H:i:s');

                        $endTodayUtc = $localNow->copy()
                            ->endOfDay()
                            ->utc()
                            ->format('Y-m-d H:i:s');

                        // ── Find the first unfinished, numbered-round match TODAY ────────
                        //    We anchor on today's local date, not a calendar week window.
                        //    This correctly handles tournaments like the World Cup where
                        //    multiple rounds (e.g. week=2 and week=3) fall within the same
                        //    Sun→Sat window — a calendar week query would always return the
                        //    earlier round's matches and block the later round from firing.
                        //
                        //    week=0 is excluded: those matches have no round number and
                        //    would produce a meaningless notification body.
                        $firstMatch = Matche::where('competition_id', $league->id)
                            ->where('match_date', '>=', $startTodayUtc)
                            ->where('match_date', '<=', $endTodayUtc)
                            ->where('week', '!=', '0')
                            ->whereRaw("week REGEXP '^[0-9]+$'")
                            ->whereNotIn('status', $finishedStatuses)
                            ->orderBy('match_date')
                            ->first();

                        if (!$firstMatch) {
                            if ($isDryRun) {
                                $this->line('⏭️  ' . $league->translate('en')->name . ' [' . $timezone . '] — no unfinished numbered-round matches today');
                            }
                            $totalSkipped++;
                            continue;
                        }

                        // ── Only notify on the first day of this round ──────────────────
                        //    If this round started yesterday or earlier (some matches
                        //    already played), skip — the notification already fired on the
                        //    round's first day.
                        $roundFirstMatchUtc = Matche::where('competition_id', $league->id)
                            ->where('week', $firstMatch->week)
                            ->orderBy('match_date')
                            ->value('match_date');

                        $roundFirstDay = Carbon::parse($roundFirstMatchUtc)
                            ->utc()
                            ->setTimezone($timezone)
                            ->format('Y-m-d');

                        if ($roundFirstDay !== $localToday) {
                            if ($isDryRun) {
                                $this->line('⏭️  ' . $league->translate('en')->name . ' [' . $timezone . '] — round ' . $firstMatch->week . ' started on ' . $roundFirstDay . ', not today');
                            }
                            $totalSkipped++;
                            continue;
                        }

                        $roundLabelEn = $this->getRoundLabelEn((int) $firstMatch->week, $league->id);
                        $roundLabelAr = $this->getRoundLabelAr((int) $firstMatch->week, $league->id);

                        // ── Warn in dry-run if translations look suspicious ──────────────
                        //    e.g. "Premier League" EN with a completely different AR name
                        //    suggests a bad translation record in the DB.
                        if ($isDryRun) {
                            $nameEn = $league->translate('en')->name ?? '';
                            $nameAr = $league->translate('ar')->name ?? '';
                            if (empty($nameAr)) {
                                $this->warn('   ⚠️  Missing AR translation for competition ID ' . $league->id . ' ("' . $nameEn . '")');
                            }
                        }

                        $notifyPayload = [
                            'screen'         => '1',
                            'round'          => (string) $firstMatch->week,
                            'competition_id' => (string) $league->id,
                            'league_id'      => (string) $league->league_id,
                            'competition_en' => $league->translate('en')->name,
                            'competition_ar' => $league->translate('ar')->name,
                            'image'          => (string) ($league->image_path ?? ''),
                        ];

                        $tokens_en = $clients->where('locale', 'en')->pluck('fb_token')->toArray();
                        $tokens_ar = $clients->where('locale', 'ar')->pluck('fb_token')->toArray();

                        if ($isDryRun) {
                            $isKnockout   = $this->isKnockoutCup($league->id);
                            $cupOrLeague  = $isKnockout ? '🏆 Knockout Cup' : '🏟️  League / Sequential Cup';

                            $this->info(
                                '✅ WOULD SEND: ' . $league->translate('en')->name .
                                ' (ID:' . $league->id . ')' .
                                ' [' . $timezone . ']' .
                                ' | Week: ' . $firstMatch->week .
                                ' | EN tokens: ' . count($tokens_en) .
                                ' | AR tokens: ' . count($tokens_ar) .
                                ' | First match: ' . $localToday
                            );
                            $this->line('   Type       : ' . $cupOrLeague);
                            $this->line('   AR name    : ' . ($league->translate('ar')->name ?? '⚠️ MISSING'));
                            $this->line('   Today range : ' . $startTodayUtc . ' → ' . $endTodayUtc);
                            $this->line('   ┌─ EN notification ──────────────────────────────');
                            $this->line('   │  Title : ' . $league->translate('en')->name);
                            $this->line('   │  Body  : ' . $roundLabelEn . ' of ' . $league->translate('en')->name . ' starts today');
                            $this->line('   ├─ AR notification ──────────────────────────────');
                            $this->line('   │  Title : ' . ($league->translate('ar')->name ?? '⚠️ MISSING'));
                            $this->line('   │  Body  : ' . 'انطلاق ' . $roundLabelAr . ' من ' . ($league->translate('ar')->name ?? '⚠️ MISSING') . ' اليوم');
                            $this->line('   └─────────────────────────────────────────────────');

                            $totalSent++;
                            continue;
                        }

                        // ── Only reaches here in real run ──────────────────
                        if (!empty($tokens_en)) {
                            $this->topicNotifyByFirebaseTokens($tokens_en, [
                                'title'  => $league->translate('en')->name,
                                'body'   => $roundLabelEn . ' of ' . $league->translate('en')->name . ' starts today',
                                'image'  => $league->image_path,
                                'notify' => $notifyPayload,
                            ]);
                        }

                        if (!empty($tokens_ar)) {
                            $this->topicNotifyByFirebaseTokens($tokens_ar, [
                                'title'  => $league->translate('ar')->name,
                                'body'   => 'انطلاق ' . $roundLabelAr . ' من ' . $league->translate('ar')->name . ' اليوم',
                                'image'  => $league->image_path,
                                'notify' => $notifyPayload,
                            ]);
                        }

                        $totalSent++;
                        $this->info('Sent for league: ' . $league->translate('en')->name . ' timezone: ' . $timezone);
                        Log::info('favourite_league sent', [
                            'league'   => $league->translate('en')->name,
                            'timezone' => $timezone,
                            'round'    => $firstMatch->week,
                            'tokens'   => count($tokens_en) + count($tokens_ar),
                        ]);
                    }

                } catch (\Exception $e) {
                    Log::error('favourite_league league failed', [
                        'league_id' => $league->id,
                        'error'     => $e->getMessage(),
                    ]);
                }
            }

            $this->info('─────────────────────────────────────────────');
            $this->info('Total would send: ' . $totalSent);
            $this->info('Total skipped:    ' . $totalSkipped);

            if ($isDryRun) {
                $this->info('🔍 DRY RUN COMPLETE — nothing was sent');
            }

        } catch (\Exception $e) {
            Log::error('favourite_league command failed: ' . $e->getMessage());
        }

        $this->info('Finished sending league notifications.');
        return 0;
    }

    /**
     * Determines if a competition uses knockout-style week codes (2, 4, 8, 16, 32, 64...)
     * vs sequential week numbers (1, 2, 3, 4, 5...).
     *
     * Strategy:
     *  1. Fetch all non-zero numeric weeks ≤ 200 and sort them.
     *  2. Count consecutive pairs (e.g. 6→7, 7→8). Leagues/sequential cups have many;
     *     pure knockout cups have none (isolated power-of-2 values like 2,4,8,16,32,64).
     *  3. HYBRID guard: tournaments like the World Cup store group-stage matches with
     *     week=0 or non-numeric values, leaving only knockout codes (2,4,8…) in the
     *     numeric week column. To avoid misclassifying those as pure knockout cups we
     *     additionally check whether any week values of 1–8 appear that would indicate
     *     sequential group rounds exist. If 3+ of {1,2,3,4,5,6,7,8} are present the
     *     competition is treated as sequential (not pure knockout).
     *
     * Threshold: ≤ 2 consecutive pairs AND fewer than 3 sequential group-round weeks
     * → knockout cup.
     */
    private function isKnockoutCup(int $competitionId): bool
    {
        static $cache = [];

        if (!isset($cache[$competitionId])) {
            $weeks = DB::table('matches')
                ->where('competition_id', $competitionId)
                ->where('week', '!=', '0')
                ->whereRaw("week REGEXP '^[0-9]+$'")
                ->distinct()
                ->pluck('week')
                ->map(fn($w) => (int) $w)
                ->filter(fn($w) => $w <= 200)
                ->sort()
                ->values()
                ->toArray();

            if (empty($weeks)) {
                $cache[$competitionId] = false;
                return false;
            }

            // ── Count consecutive pairs ────────────────────────────────────
            $consecutivePairs = 0;
            for ($i = 0; $i < count($weeks) - 1; $i++) {
                if ($weeks[$i + 1] - $weeks[$i] === 1) {
                    $consecutivePairs++;
                }
            }

            // ── Hybrid guard: count how many "group stage" week numbers
            //    (1–8) are present. If 3 or more exist the competition uses
            //    sequential rounds even if they look sparse in the DB.
            $groupStageWeeks = array_filter($weeks, fn($w) => $w >= 1 && $w <= 8);

            $isKnockout = $consecutivePairs <= 2 && count($groupStageWeeks) < 3;

            $cache[$competitionId] = $isKnockout;
        }

        return $cache[$competitionId];
    }

    /**
     * For a knockout competition, validate that the given $week is genuinely
     * the active/highest round — i.e. no unfinished matches exist in a higher
     * bracket round (week*2, week*4, …) for this competition.
     *
     * Without this check, week=2 gets labelled "The Final" even during the
     * group stage of a tournament like the World Cup where week=2 means
     * "matchday 2 of group stage" in the data, not the actual final.
     *
     * Returns true  → week is legitimately the highest active round (safe to label).
     * Returns false → higher rounds exist with unfinished matches; fall back to
     *                 generic "Round N" label.
     */
    private function isHighestActiveKnockoutRound(int $week, int $competitionId): bool
    {
        if ($week === 0) {
            return false;
        }

        $finishedStatuses = ['FT', 'AET', 'PEN', 'PST', 'CANC', 'SUSP', 'ABD', 'AWD', 'WO'];

        // Build the list of bracket rounds that would be "higher" than $week.
        // In knockout nomenclature the higher the week code, the earlier the round
        // (64 = R64, 32 = R32, 16 = R16, 8 = QF, 4 = SF, 2 = Final).
        // So "higher rounds" means weeks with a larger numeric value.
        $higherRounds = DB::table('matches')
            ->where('competition_id', $competitionId)
            ->where('week', '>', $week)           // numerically larger = earlier round
            ->whereRaw("week REGEXP '^[0-9]+$'")
            ->whereNotIn('status', $finishedStatuses)
            ->exists();

        // If any unfinished match exists in a higher-numbered round, $week cannot
        // be the final/last round yet.
        return !$higherRounds;
    }

    private function getRoundLabelEn(int $week, int $competitionId): string
    {
        // week=0 is filtered out upstream — $week is always ≥ 1 here.
        if (!$this->isKnockoutCup($competitionId)) {
            return 'Round ' . $week;
        }

        // Extra safety: only use knockout-specific labels when this round is
        // genuinely the highest active bracket round in the DB.
        if (!$this->isHighestActiveKnockoutRound($week, $competitionId)) {
            return 'Round ' . $week;
        }

        return match($week) {
            2    => 'The Final',
            3    => 'The 3rd Place Match',
            4    => 'The Semi-Finals',
            8    => 'The Quarter-Finals',
            16   => 'The Round of 16',
            32   => 'The Round of 32',
            64   => 'The Round of 64',
            128  => 'The Round of 128',
            1128 => 'The Round of 128',
            1256 => 'The Round of 128',
            89   => 'The Conference Semi-Finals',
            91   => 'The Conference Finals',
            default => 'Round ' . $week,
        };
    }

    private function getRoundLabelAr(int $week, int $competitionId): string
    {
        // week=0 is filtered out upstream — $week is always ≥ 1 here.
        if (!$this->isKnockoutCup($competitionId)) {
            return 'الجولة ' . $week;
        }

        // Extra safety: only use knockout-specific labels when this round is
        // genuinely the highest active bracket round in the DB.
        if (!$this->isHighestActiveKnockoutRound($week, $competitionId)) {
            return 'الجولة ' . $week;
        }

        return match($week) {
            2    => 'المباراة النهائية',
            3    => 'مباراة تحديد المركز الثالث',
            4    => 'الدور نصف النهائي',
            8    => 'دور ربع النهائي',
            16   => 'دور الـ 16',
            32   => 'دور الـ 32',
            64   => 'دور الـ 64',
            128  => 'دور الـ 128',
            1128 => 'دور الـ 128',
            1256 => 'دور الـ 128',
            89   => 'الدور نصف النهائي للمؤتمر',
            91   => 'نهائي المؤتمر',
            default => 'الجولة ' . $week,
        };
    }
}