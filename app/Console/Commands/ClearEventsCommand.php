<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ClearEventsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clear:events';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command clears the in-app notifications generated from match events (goals / cards ...). The match_events rows themselves are never touched — they hold every match score.';

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
        // Delete only the NOTIFICATIONS produced by match events. The
        // match_events rows must stay:
        //  - they are the only source of every match score (0-0 bug came
        //    from deleting goals here), and
        //  - their event_type = 'AUTO' marker is what stops the same event
        //    from being pushed to clients twice.
        // notification_translations rows go away automatically
        // (ON DELETE CASCADE on notification_id).
        $deleted = DB::table('notifications')
            ->whereIn('type', ['goal', 'events'])
            ->delete();

        $file = new Filesystem;
        $file->cleanDirectory(public_path() . "/storage/vs_images/");

        Log::info('ClearEvents: deleted ' . $deleted . ' event notifications and cleared vs_images.');
        $this->info('Cleared ' . $deleted . ' event notifications and vs_images directory.');

        return 0;
    }

}
