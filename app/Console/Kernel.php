<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Models\testtest;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
        \App\Console\Commands\SendNotificationDailyMatches::class,
        \App\Console\Commands\getMatchesFromApiAndSaveOnDatabaseEvery15Days::class,
        \App\Console\Commands\UpdateMatchesEvents::class,
        \App\Console\Commands\UpdateMatchesStatus::class,
        \App\Console\Commands\SendReminderBefore45Minutes::class,
        \App\Console\Commands\SendNotificationFavouriteLeague::class,
        \App\Console\Commands\SendNotificationLineupReady::class,

    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule): void
    {
        // ─────────────────────────────────────────────────────────────────
        // 1. EVERY MINUTE — Live events & status (highest priority)
        // ─────────────────────────────────────────────────────────────────

        // Polls API for goals, cards, VAR; guarded by testtest.enable flag
        $schedule->command('events:update')
            ->everyMinute()
            ->withoutOverlapping(5)
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/events_update.log'));

        // Fetches live fixture status & fires per-event notifications
        $schedule->command('live:get')
            ->everyMinute()
            ->withoutOverlapping(5)
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/live_get.log'));

        // Sends FT / AET / PEN / BT end-of-match notifications
        $schedule->command('end:match')
            ->everyMinute()
            ->withoutOverlapping(5)
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/end_match.log'));

        // ─────────────────────────────────────────────────────────────────
        // 2. EVERY MINUTE — Pre-match reminders
        // ─────────────────────────────────────────────────────────────────

        // Notifies favourite-team users 45 min before kick-off (per-timezone)
        $schedule->command('send:reminder')
            ->everyMinute()
            ->withoutOverlapping(5)
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/send_reminder.log'));

        // Kick-off alerts for third-degree matches (no fixture_id)
        $schedule->command('theird:send')
            ->everyMinute()
            ->withoutOverlapping(5)
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/theird_send.log'));

        // ─────────────────────────────────────────────────────────────────
        // 3. LINEUP AVAILABILITY
        // ─────────────────────────────────────────────────────────────────

        // Legacy lineup notifier — kept as fallback, remove when confident
        $schedule->command('lineup:ready')
            ->everyMinute()
            ->withoutOverlapping(5)
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/lineup_ready.log'));

        // ─────────────────────────────────────────────────────────────────
        // 4. DAILY — Match data ingestion
        // ─────────────────────────────────────────────────────────────────

        // Persist today's fixtures, teams, competitions from API
        $schedule->command('save:matches')
            ->dailyAt('00:05')
            ->withoutOverlapping(5)
            ->appendOutputTo(storage_path('logs/save_matches.log'));

        // Pre-load next 3 days of fixtures (+1 → +3)
        $schedule->command('future:matches')
            ->daily()
            ->withoutOverlapping(5)
            ->appendOutputTo(storage_path('logs/future_matches.log'));

        // ─────────────────────────────────────────────────────────────────
        // 5. DAILY — User-facing notifications (per-client timezone)
        //
        //    These commands now run every minute and handle timezone logic
        //    internally: each one checks whether the current moment equals
        //    the target local hour for each client's stored timezone.
        // ─────────────────────────────────────────────────────────────────

        // Fan-out: reads league IDs from Settings, calls notification:daily {id}
        // Target local time: 07:00 in each client's timezone
        $schedule->command('notification:daily')
            ->everyMinute()
            ->withoutOverlapping(5)
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/run_daily_notifications.log'));

        // Fan-out: reads league IDs from Settings, calls notification:weekly {id}
        // Target local time: Monday 07:30 in each client's timezone
        $schedule->command('notification:weekly')
            ->everyMinute()
            ->withoutOverlapping(5)
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/run_weekly_notifications.log'));

        // "Your favourite team plays today"
        // Target local time: 08:00 in each client's timezone
        $schedule->command('notification:favourite_team')
            ->everyMinute()
            ->withoutOverlapping(5)
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/notification_favourite_team.log'));

        // "Round X of League Y starts today" — new version
        // Target local time: 08:30 in each client's timezone
        $schedule->command('notification:favourite_league')
            ->everyMinute()
            ->withoutOverlapping(5)
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/notification_favourite_league.log'));

        // Legacy version — remove once new command confirmed stable
        // Target local time: 08:45 in each client's timezone
        // $schedule->command('notification:favourite_league_old')
        //     ->everyMinute()
        //     ->withoutOverlapping(5)
        //     ->runInBackground()
        //     ->appendOutputTo(storage_path('logs/notification_favourite_league_old.log'));

        // ─────────────────────────────────────────────────────────────────
        // 6. DAILY — Cleanup
        // ─────────────────────────────────────────────────────────────────

        // Purge AUTO MatchEvent rows + vs_images directory
        $schedule->command('clear:events')
            ->dailyAt('03:00')
            ->appendOutputTo(storage_path('logs/clear_events.log'));

        $schedule->command('notification:static')
            ->dailyAt('9:00')
            ->appendOutputTo(storage_path('logs/static_reminder.log'));
    }


    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}