<?php

namespace App\Console\Commands;

use App\Models\Post;
use App\Services\PostNotificationService;
use Illuminate\Console\Command;

class PublishScheduledPosts extends Command
{
    protected $signature = 'posts:publish-scheduled';

    protected $description = 'Send the push notification for scheduled posts once their publish time is due';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(PostNotificationService $notifier): int
    {
        $due = Post::query()
            ->dueForNotification()
            ->orderBy('published_at')
            ->get();

        if ($due->isEmpty()) {
            return self::SUCCESS;
        }

        foreach ($due as $post) {
            $notifier->send($post);
            $post->forceFill(['notified_at' => now()])->save();

            $this->info("Sent notification for scheduled post #{$post->id}.");
        }

        return self::SUCCESS;
    }
}
