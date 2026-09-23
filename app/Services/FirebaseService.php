<?php

namespace App\Services;

use Google\Client as GoogleClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FirebaseService
{
    protected string $fcmUrl;

    public function __construct()
    {
        $this->fcmUrl = 'https://fcm.googleapis.com/v1/projects/' . env('FIREBASE_PROJECT_ID') . '/messages:send';
    }

    public function getAccessToken(): string
    {
        return cache()->remember('firebase_access_token', 3500, function () {
            $client = new GoogleClient();
            $client->setAuthConfig(config('firebase.credentials'));
            $client->addScope('https://www.googleapis.com/auth/firebase.messaging');
            return $client->fetchAccessTokenWithAssertion()['access_token'];
        });
    }

    public function getFcmUrl(): string
    {
        return $this->fcmUrl;
    }

    public function sendNotification(array $tokens, array $data = []): void
    {
        $accessToken = $this->getAccessToken();

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
                if (is_array($value) || is_object($value)) {
                    return false; // skip anything not serializable as string
                }
                return is_string($value) || is_int($value) || is_bool($value);
            });
        }

        // Always inject title and body into data payload
        // Mobile app reads from data, not notification object
        $notify['title'] = $data['title'];
        $notify['body']  = $data['body'];
        if (array_key_exists('image', $data)) {
            $notify['image'] = $data['image'];
        }

        // FCM requires all data values to be strings
        $notify = array_map('strval', $notify);

        Log::info('FCM sending to ' . count($tokens) . ' tokens', [
            'notification' => $notification,
            'data'         => $notify,
        ]);

        foreach ($tokens as $token) {
            $payload = [
                'message' => [
                    'token'        => $token,
                    'notification' => $notification,
                    'data'         => $notify,
                    'android'      => [
                        'priority'     => 'high',
                        'notification' => [
                            'sound'      => 'default',
                            'channel_id' => 'default',
                        ],
                    ],
                    'apns' => [
                        'payload' => [
                            'aps' => [
                                'sound' => 'default',
                                'badge' => 1,
                            ],
                        ],
                    ],
                ],
            ];

            $response = Http::withToken($accessToken)
                ->post($this->fcmUrl, $payload);

            if ($response->failed()) {
                $body      = $response->json();
                $errorCode = $body['error']['details'][0]['errorCode'] ?? null;
                $status    = $response->status();

                // ── Stale token cleanup ───────────────────────────────────
                // FCM returns 404 + UNREGISTERED when the device token is no
                // longer valid (app uninstalled, token rotated, etc.).
                // Keeping these tokens wastes API quota and pollutes logs,
                // so we null them out immediately rather than deleting the
                // client row (preserving the client's other data/preferences).
                if ($status === 404 && $errorCode === 'UNREGISTERED') {
                    DB::table('clients')
                        ->where('fb_token', $token)
                        ->update(['fb_token' => null]);

                    Log::info('FCM stale token removed from clients', [
                        'token' => substr($token, 0, 30) . '...',
                    ]);

                    continue;
                }

                Log::error('FCM send failed', [
                    'token'  => substr($token, 0, 30) . '...',
                    'status' => $status,
                    'body'   => $body,
                ]);
            } else {
                Log::info('FCM send success', [
                    'token'    => substr($token, 0, 30) . '...',
                    'response' => $response->json(),
                ]);
            }
        }
    }
}