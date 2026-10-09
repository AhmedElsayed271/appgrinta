<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Notification;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Creates (or updates) a dummy client that owns one notification of every
 * type we support, so the mobile team can log in / use the API token and
 * exercise the whole range of in-app notifications.
 *
 * Run it with:
 *   php artisan db:seed --class=Database\\Seeders\\MobileNotificationsDemoSeeder
 *
 * You can override the defaults with env vars:
 *   MOBILE_DEMO_EMAIL=someone@example.com
 *   MOBILE_DEMO_TOKEN=fixed-api-token
 */
class MobileNotificationsDemoSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('MOBILE_DEMO_EMAIL', 'alsayedahmed370@gmail.com');
        $token = env('MOBILE_DEMO_TOKEN', 'mobile-demo-test-token-1234567890');

        $client = Client::query()->firstOrNew(['email' => $email]);
        if (! $client->exists) {
            $client->full_name         = 'Mobile Demo Client';
            $client->password          = Hash::make(Str::random(16));
            $client->email_verified_at = now();
        }
        $client->locale    = 'en';
        $client->timezone  = $client->timezone ?: 'Africa/Cairo';
        $client->api_token = $token;
        $client->fb_token  = $client->fb_token ?: 'expo_push_token_here';
        $client->save();

        // ── Clean previous demo notifications for this client (idempotent) ──
        $existingIds = Notification::query()->where('client_id', $client->id)->pluck('id');
        if ($existingIds->isNotEmpty()) {
            DB::table('notification_translations')->whereIn('notification_id', $existingIds)->delete();
            Notification::query()->where('client_id', $client->id)->delete();
        }

        // ── Optional real reference ids (so mobile navigation works) ───────
        $match = DB::table('matches')->select('id', 'fixture_id', 'team1_id', 'team2_id')->first();
        $team  = DB::table('teams')->select('id', 'team_id')->first();
        $comp  = DB::table('competitions')->select('id', 'league_id')->first();
        $post  = DB::table('posts')->select('id')->first();

        $matchId = (string) ($match->id ?? 1);
        $fixture = (string) ($match->fixture_id ?? 1);
        $home    = (string) ($match->team1_id ?? 1);
        $away    = (string) ($match->team2_id ?? 2);
        $teamApi = (string) ($team->team_id ?? 1);
        $compId  = (string) ($comp->id ?? 1);
        $league  = (string) ($comp->league_id ?? 1);
        $postId  = (string) ($post->id ?? 1);

        $image = rtrim(config('app.url'), '/')
            . '/storage/uploads/manual_notifications_images/today_matches.png';

        $notifications = [
            ['goal', [
                'type' => 'goal', 'match_id' => $matchId, 'fixture_id' => $fixture,
                'team_id' => $home, 'player_name' => 'Vinicius Jr', 'minute' => '23',
            ], 'ريال مدريد 1 - 0 برشلونة', 'سجل فينيسيوس جونيور هدفاً لصالح ريال مدريد',
               'Real Madrid 1 - 0 Barcelona', 'Vinicius Jr scored a goal for Real Madrid'],

            ['events', [
                'type' => 'events', 'match_id' => $matchId, 'fixture_id' => $fixture,
                'team_id' => $away, 'player_name' => 'Pedri', 'minute' => '41', 'detail' => 'yellow card',
            ], 'ريال مدريد 1 - 0 برشلونة', 'حصل اللاعب بيدري على كرت أصفر',
               'Real Madrid 1 - 0 Barcelona', 'Pedri took yellow card'],

            ['match_status', [
                'type' => 'match_status', 'sub_type' => 'match_started', 'match_id' => $matchId,
                'fixture_id' => $fixture, 'home_team_id' => $home, 'away_team_id' => $away,
            ], 'بداية المباراة', 'ريال مدريد - برشلونة انطلقت الآن', 'Match Started', 'Real Madrid - Barcelona has started'],

            ['match_status', [
                'type' => 'match_status', 'sub_type' => 'half_time', 'match_id' => $matchId,
                'fixture_id' => $fixture, 'home_team_id' => $home, 'away_team_id' => $away,
            ], 'نهاية الشوط الأول', 'انتهى الشوط الأول من ريال مدريد - برشلونة', 'Half Time', 'First half of Real Madrid - Barcelona has ended'],

            ['match_status', [
                'type' => 'match_status', 'sub_type' => 'second_half', 'match_id' => $matchId,
                'fixture_id' => $fixture, 'home_team_id' => $home, 'away_team_id' => $away,
            ], 'بداية الشوط الثاني', 'بدأ الشوط الثاني من ريال مدريد - برشلونة', 'Second Half', 'Second half of Real Madrid - Barcelona has started'],

            ['match_status', [
                'type' => 'match_status', 'sub_type' => 'extra_time', 'match_id' => $matchId,
                'fixture_id' => $fixture, 'home_team_id' => $home, 'away_team_id' => $away,
            ], 'بداية الأشواط الإضافية', 'بدأ الوقت الإضافي من ريال مدريد - برشلونة', 'Extra Time', 'Extra time of Real Madrid - Barcelona has started'],

            ['match_status', [
                'type' => 'match_status', 'sub_type' => 'penalty', 'match_id' => $matchId,
                'fixture_id' => $fixture, 'home_team_id' => $home, 'away_team_id' => $away,
            ], 'بداية ركلات الجزاء', 'بدأت ركلات الجزاء في ريال مدريد - برشلونة', 'Penalty', 'Penalty shootout of Real Madrid - Barcelona has started'],

            ['match_status', [
                'type' => 'match_status', 'sub_type' => 'match_ended', 'match_id' => $matchId,
                'fixture_id' => $fixture, 'home_team_id' => $home, 'away_team_id' => $away,
            ], 'انتهت المباراة', 'انتهت مباراة ريال مدريد - برشلونة', 'Match Ended', 'Real Madrid - Barcelona has ended'],

            ['reminder', [
                'type' => 'reminder', 'match_id' => $matchId, 'fixture_id' => $fixture,
                'home_team_id' => $home, 'away_team_id' => $away, 'lineup' => 'ok',
            ], 'التشكيل جاهز', 'تشكيلة الفريقين متاحة الآن', 'Lineup Ready', 'The lineup for both teams is available now'],

            ['reminder', [
                'type' => 'reminder', 'url' => 'matches',
            ], 'مباريات اليوم', 'تابع أهم مباريات اليوم ومواعيدها على جرينتا',
               "Today's Matches", "Check today's top matches on Grinta"],

            ['reminder', [
                'type' => 'reminder', 'match_id' => $matchId, 'fixture_id' => $fixture,
            ], 'تذكير قبل المباراة', 'باقي 45 دقيقة على انطلاق ريال مدريد - برشلونة',
               'Match Reminder', '45 minutes left until Real Madrid - Barcelona'],

            ['announcement', [
                'type' => 'announcement', 'url' => rtrim(env('APP_URL', 'https://appgrinta.com'), '/'),
            ], 'إعلان', 'تم إطلاق تحديث جديد لتطبيق جرينتا', 'Announcement', 'A new Grinta app update is now available'],

            ['announcement', [
                'type' => 'announcement', 'match_id' => $matchId,
            ], 'إشعار مخصص', 'تفاصيل خاصة بمباراة ريال مدريد - برشلونة', 'Custom Notification', 'Special details for Real Madrid - Barcelona'],

            ['post', [
                'type' => 'post', 'post_id' => $postId,
            ], 'بوست جديد', 'شاهد أحدث بوست على جرينتا', 'New Post', 'Check out the latest post on Grinta'],

            ['team', [
                'type' => 'team', 'match_id' => $matchId, 'fixture_id' => $fixture, 'team_id' => $teamApi,
            ], 'ريال مدريد', 'فريقك المفضل ريال مدريد لديه مباراة اليوم', 'Real Madrid', 'Your favourite team Real Madrid has a match today'],

            ['league', [
                'type' => 'league', 'competition_id' => $compId, 'league_id' => $league,
                'round' => '5', 'screen' => '1',
            ], 'الدوري الإسباني', 'انطلاق الجولة 5 من الدوري الإسباني اليوم', 'La Liga', 'Round 5 of La Liga starts today'],
        ];

        $base = now()->subMinutes(count($notifications));

        foreach ($notifications as $i => $item) {
            [$type, $payload, $titleAr, $bodyAr, $titleEn, $bodyEn] = $item;

            $createdAt = $base->copy()->addMinutes($i);

            $notification = new Notification();
            $notification->client_id  = $client->id;
            $notification->type       = $type;
            $notification->image      = $image;
            $notification->data       = json_encode($payload, JSON_UNESCAPED_UNICODE);
            $notification->is_read    = false;
            $notification->created_at = $createdAt;
            $notification->updated_at = $createdAt;

            $notification->translateOrNew('ar')->title = $titleAr;
            $notification->translateOrNew('ar')->body  = $bodyAr;
            $notification->translateOrNew('en')->title = $titleEn;
            $notification->translateOrNew('en')->body  = $bodyEn;

            $notification->save();
        }

        if ($this->command) {
            $this->command->info("Mobile demo seeder done.");
            $this->command->line("  client id : {$client->id}");
            $this->command->line("  email     : {$client->email}");
            $this->command->line("  api_token : {$client->api_token}");
            $this->command->line('  notifications: ' . count($notifications));
        }
    }
}
