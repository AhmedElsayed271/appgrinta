<?php

namespace App\Services;

use App\Models\Post;
use App\Traits\Notify;
use Illuminate\Support\Facades\Log;

class PostNotificationService
{
    use Notify;

    /**
     * Send the push notification for a post to all clients (en + ar).
     */
    public function send(Post $post): void
    {
        try {
            $tokens_en = array_filter(array_unique(array_merge(
                \App\Models\Client::where('locale', 'en')->whereNotNull('fb_token')->pluck('fb_token')->toArray(),
                \App\Models\Guest::tokensEn()
            )));

            $tokens_ar = array_filter(array_unique(array_merge(
                \App\Models\Client::where('locale', 'ar')->whereNotNull('fb_token')->pluck('fb_token')->toArray(),
                \App\Models\Guest::tokensAr()
            )));

            $imageUrl = $post->image
                ? asset('storage/uploads/post_images/' . $post->image)
                : '';

            $notify = [
                'type' => 'post',
                'id' => (string)$post->id,
            ];

            // English
            if (!empty($tokens_en)) {
                $titleEn = $post->translate('en')?->name ?? $post->translate('ar')?->name ?? 'New Post';
                $bodyEn = $post->translate('en')?->description
                    ? substr(strip_tags($post->translate('en')->description), 0, 100) . '...'
                    : '';

                $this->topicNotifyByFirebaseTokens($tokens_en, [
                    'title' => $titleEn,
                    'body' => $bodyEn,
                    'image' => $imageUrl,
                    'notify' => $notify,
                ]);
            }

            // Arabic
            if (!empty($tokens_ar)) {
                $titleAr = $post->translate('ar')?->name ?? $post->translate('en')?->name ?? 'منشور جديد';
                $bodyAr = $post->translate('ar')?->description
                    ? substr(strip_tags($post->translate('ar')->description), 0, 100) . '...'
                    : '';

                $this->topicNotifyByFirebaseTokens($tokens_ar, [
                    'title' => $titleAr,
                    'body' => $bodyAr,
                    'image' => $imageUrl,
                    'notify' => $notify,
                ]);
            }

        } catch (\Exception $e) {
            // Don't fail the post creation if notification fails
            Log::error('Auto post notification failed', [
                'post_id' => $post->id,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
