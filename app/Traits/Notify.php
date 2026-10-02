<?php

namespace App\Traits;

use App\Jobs\SendFirebaseNotifications;
use App\Models\Setting;
use App\Services\FirebaseService;
use App\Services\NotificationPersistence;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

trait Notify
{
    /**
     * When true, every push sent through this trait is also stored as an
     * in-app (database) notification for the matching clients. Set to false
     * in callers that persist the notification themselves to avoid duplicates.
     */
    protected bool $persistNotifications = true;
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

        if (isset($data['notify']) && is_array($data['notify'])) {
            $data['notify'] = NotificationPersistence::normalizePayload($data['notify']);
        }

        if ($this->persistNotifications ?? true) {
            $this->persistInApp($tokens, $data);
        }

        // Always dispatch to job to avoid request timeout
        dispatch(new SendFirebaseNotifications($tokens, $data))->afterResponse();
    }

    /**
     * Store an in-app (database) notification for the clients owning the tokens.
     */
    protected function persistInApp(array $tokens, array $data = []): void
    {
        try {
            (new NotificationPersistence())->persistFromTokens($tokens, $data);
        } catch (\Throwable $e) {
            Log::error('Notification persistence failed', [
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send notification directly via FirebaseService.
     */
    public function sendNotification(array $tokens, array $data = []): void
    {
        if (isset($data['notify']) && is_array($data['notify'])) {
            $data['notify'] = NotificationPersistence::normalizePayload($data['notify']);
        }

        if ($this->persistNotifications ?? true) {
            $this->persistInApp($tokens, $data);
        }

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
