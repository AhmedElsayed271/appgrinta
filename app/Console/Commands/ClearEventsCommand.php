<?php

namespace App\Console\Commands;

use App\Models\MatchEvent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Filesystem\Filesystem;
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
    protected $description = 'This command clears all events from database that have been sent as notifications and get players name that not recorded.';

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
        $deleted = MatchEvent::whereNotNull('event_type')
            ->where('event_type', 'AUTO')
            ->delete();

        $file = new Filesystem;
        $file->cleanDirectory(public_path() . "/storage/vs_images/");

        Log::info('ClearEvents: deleted ' . $deleted . ' events and cleared vs_images.');
        $this->info('Cleared ' . $deleted . ' events and vs_images directory.');
    }
    
}
