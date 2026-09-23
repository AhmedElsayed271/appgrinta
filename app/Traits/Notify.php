<?php

namespace App\Traits;

use App\Jobs\SendFirebaseNotifications;
use App\Models\Setting;
use App\Services\FirebaseService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

trait Notify
{
    /**
     * Send notification to a Firebase topic using HTTP v1 API.
     */
    public function topicNotifyByFirebase(string $topic, array $data = []): void
    {
        $firebaseService = new FirebaseService();

        try {
            $accessToken = $firebaseService->getAccessToken();

            $notification = [
                'title' => $data['title'],
                'body'  => $data['body'],
            ];

            if (array_key_exists('image', $data)) {
                $notification['image'] = $data['image'];
            }

            $notify = [];
            if (array_key_exists('notify', $data)) {
                $notify = array_filter((array) $data['notify'], function ($value) {
                    return is_string($value) || is_int($value);
                });
            }

            $payload = [
                'message' => [
                    'topic'        => $topic,
                    'notification' => $notification,
                    'data'         => $notify,
                ],
            ];

            $response = \Illuminate\Support\Facades\Http::withToken($accessToken)
                ->post($firebaseService->getFcmUrl(), $payload);

            if ($response->failed()) {
                Log::error('FCM topic notification failed', [
                    'topic'  => $topic,
                    'status' => $response->status(),
                    'body'   => $response->json(),
                ]);
            } else {
                Log::info('FCM topic notification sent', [
                    'topic'    => $topic,
                    'response' => $response->json(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('FCM topic notification exception', [
                'topic'   => $topic,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send notification to multiple tokens, dispatching a job if over 999 tokens.
     */
    public function topicNotifyByFirebaseTokens(array|Collection $tokens, array $data = []): void
    {
        if ($tokens instanceof Collection) {
            $tokens = $tokens->toArray();
        }

        // Remove null/empty tokens
        $tokens = array_values(array_filter($tokens));

        if (count($tokens) === 0) {
            Log::warning('FCM: topicNotifyByFirebaseTokens called with empty token list');
            return;
        }

        // Always dispatch to job to avoid request timeout
        dispatch(new SendFirebaseNotifications($tokens, $data))->afterResponse();
    }

    /**
     * Send notification directly via FirebaseService.
     */
    public function sendNotification(array $tokens, array $data = []): void
    {
        $firebaseService = new FirebaseService();
        $firebaseService->sendNotification($tokens, $data);
    }

    /**
     * Split an array into $p roughly equal partitions.
     */
    public function partition(array $list, int $p): array
    {
        $listlen   = count($list);
        $partlen   = (int) floor($listlen / $p);
        $partrem   = $listlen % $p;
        $partition = [];
        $mark      = 0;

        for ($px = 0; $px < $p; $px++) {
            $incr           = ($px < $partrem) ? $partlen + 1 : $partlen;
            $partition[$px] = array_slice($list, $mark, $incr);
            $mark          += $incr;
        }

        return $partition;
    }
}
