<?php

namespace App\Console\Commands;

use App\Traits\NotificationOfMatchesTrait;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StaticCommand extends Command
{
    use NotificationOfMatchesTrait;

    protected $signature   = 'notification:static';
    protected $description = 'Send static notification to all clients to remind them to check today matches.';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(): int
    {
        try {
            $imageUrl = rtrim(env('APP_URL'), '/') . '/storage/uploads/manual_notifications_images/today_matches.png';

            $notifyPayload = [
                'url'   => 'matches',
                'image' => $imageUrl,
            ];

            $client_tokens_en = DB::table('clients')
                ->where('locale', 'en')
                ->whereNotNull('fb_token')
                ->pluck('fb_token')
                ->toArray();

            $client_tokens_ar = DB::table('clients')
                ->where('locale', 'ar')
                ->whereNotNull('fb_token')
                ->pluck('fb_token')
                ->toArray();

            $this->topicNotifyByFirebaseTokens($client_tokens_en, [
                'title'  => "Today's Matches",
                'body'   => "Check today's top football matches and schedules on Grinta",
                'image'  => $imageUrl,
                'notify' => $notifyPayload,
            ]);

            $this->topicNotifyByFirebaseTokens($client_tokens_ar, [
                'title'  => 'تعرف على مباريات اليوم ⚽',
                'body'   => 'تابع أهم مباريات اليوم ومواعيدها مباشرة من جرينتا.',
                'image'  => $imageUrl,
                'notify' => $notifyPayload,
            ]);

            $this->info('Static notifications sent successfully.');

        } catch (\Exception $e) {
            Log::error('notification:static failed: ' . $e->getMessage());
        }

        return 0;
    }
}