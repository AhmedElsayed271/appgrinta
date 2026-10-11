<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ClearNotificationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clear:notifications {--dry-run : Show how many rows would be deleted without deleting them}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete old in-app notifications based on a per-type retention window (24h / 48h / 7d).';

    /**
     * Retention window (in hours) per notification type.
     *  - events, match_status, team, league  => 48 hours
     *  - goal, reminder                     => 24 hours
     *  - post, announcement                 => 7 days (168 hours)
     *
     * @var array<string,int>
     */
    protected const RETENTION_HOURS = [
        'events'       => 48,
        'match_status' => 48,
        'team'         => 48,
        'league'       => 48,
        'goal'         => 24,
        'reminder'     => 24,
        'post'         => 168,
        'announcement' => 168,
    ];

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
        $dryRun  = (bool) $this->option('dry-run');
        $total   = 0;

        foreach (static::RETENTION_HOURS as $type => $hours) {
            $cutoff = now()->subHours($hours);

            $query = DB::table('notifications')
                ->where('type', $type)
                ->where('created_at', '<', $cutoff);

            $count = (clone $query)->count();
            $total += $count;

            if ($count > 0 && ! $dryRun) {
                $query->delete();
            }

            $this->line(sprintf(
                '%-14s older than %3dh : %d %s',
                $type,
                $hours,
                $count,
                $dryRun ? '(dry-run)' : 'deleted'
            ));
        }

        $message = sprintf(
            'clear:notifications %s: %d notification(s).',
            $dryRun ? 'preview' : 'deleted',
            $total
        );

        Log::info($message);
        $this->info($message);

        return 0;
    }
}
