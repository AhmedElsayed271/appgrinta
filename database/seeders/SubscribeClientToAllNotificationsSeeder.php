<?php

namespace Database\Seeders;

use App\Models\Client;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Makes one client receive every *targeted* notification type by subscribing
 * it to all teams (favourite_team) and all competitions (favourite_competition).
 *
 * Background: in this app `announcement` / `post` already go to every client,
 * while `goal`, `events`, `match_status`, `reminder`, `team` and `league` are
 * only delivered to clients that follow the related team / competition. So
 * "receive all types" == "follow everything".
 *
 * Run it with:
 *   php artisan db:seed --class=Database\\Seeders\\SubscribeClientToAllNotificationsSeeder
 *
 * Override the target client:
 *   NOTIFY_ALL_EMAIL=someone@example.com
 */
class SubscribeClientToAllNotificationsSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('NOTIFY_ALL_EMAIL', 'alsayedahmed370@gmail.com');

        $client = Client::query()->where('email', $email)->first();

        if (! $client) {
            $this->command?->error("Client not found for email: {$email}");

            return;
        }

        $teams        = $this->attach('favourite_team', 'team_id', 'teams', $client->id);
        $competitions = $this->attach('favourite_competition', 'competition_id', 'competitions', $client->id);

        $this->command?->info(
            "Client #{$client->id} ({$email}) now follows {$teams} teams and {$competitions} competitions."
        );
    }

    /**
     * Insert the pivot rows the client is still missing (idempotent).
     */
    private function attach(string $pivot, string $foreignKey, string $sourceTable, int $clientId): int
    {
        $existing = array_flip(array_map('intval', DB::table($pivot)->where('client_id', $clientId)->pluck($foreignKey)->all()));

        $inserted = 0;

        DB::table($sourceTable)->select('id')->orderBy('id')->chunk(500, function ($rows) use ($pivot, $foreignKey, $clientId, $existing, &$inserted) {
            $insert = [];

            foreach ($rows as $row) {
                if (isset($existing[$row->id])) {
                    continue;
                }

                $insert[] = [
                    'client_id' => $clientId,
                    $foreignKey => $row->id,
                ];
            }

            if ($insert !== []) {
                DB::table($pivot)->insert($insert);
                $inserted += count($insert);
            }
        });

        return $inserted;
    }
}
