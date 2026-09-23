<?php

namespace App\Jobs;

use App\Services\FirebaseService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendFirebaseNotifications implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300; // 5 minutes
    public int $tries   = 3;

    public function __construct(
        protected array $tokens,
        protected array $data
    ) {}

    public function handle(): void
    {
        $firebaseService = new FirebaseService();

        // Process in chunks of 500 to avoid memory issues
        $chunks = array_chunk($this->tokens, 500);

        foreach ($chunks as $chunk) {
            try {
                $firebaseService->sendNotification($chunk, $this->data);
            } catch (\Exception $e) {
                Log::error('FCM chunk send failed', [
                    'message' => $e->getMessage(),
                    'tokens'  => count($chunk),
                ]);
            }
        }
    }
}
