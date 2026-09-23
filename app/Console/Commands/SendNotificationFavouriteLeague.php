<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Models\Client;
use App\Traits\Notify;
use App\Models\Competition;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\FirstMatchesOfRounds;

class SendNotificationFavouriteLeague extends Command
{
    use Notify;

    protected $signature   = 'notification:favourite_league_old';
    protected $description = 'Send notification for user when his favourite league rounds match will start (legacy) at 08:45 in each client\'s local timezone';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $timezones = Client::cachedTimezones();

        foreach ($timezones as $timezone) {
            try {
                $localNow = Carbon::now($timezone);
            } catch (\Exception $e) {
                continue;
            }

            if ($localNow->hour !== 8 || $localNow->minute !== 45) {
                continue;
            }

            $localToday = $localNow->format('Y-m-d');

            $matchesData = FirstMatchesOfRounds::whereDate('match_date', $localToday)->get();

            if ($matchesData->isEmpty()) {
                continue;
            }

            foreach ($matchesData as $matche) {

                $league = Competition::where('league_id', $matche->competition_id)->first()
                    ?? Competition::find($matche->competition_id);

                if (!$league) {
                    continue;
                }

                $client_ids = DB::table('favourite_competition')
                    ->where('competition_id', $matche->competition_id)
                    ->pluck('client_id')
                    ->toArray();

                if (empty($client_ids)) {
                    continue;
                }

                $client_tokens_en = DB::table('clients')
                    ->where('locale', 'en')
                    ->where('timezone', $timezone)
                    ->whereIn('id', $client_ids)
                    ->pluck('fb_token');

                $client_tokens_ar = DB::table('clients')
                    ->where('locale', 'ar')
                    ->where('timezone', $timezone)
                    ->whereIn('id', $client_ids)
                    ->pluck('fb_token');

                $weekNumber = abs((int) filter_var($matche->round, FILTER_SANITIZE_NUMBER_INT));

                // ── Build notify payload ───────────────────────────────────
                $notifyPayload = [
                    'screen'         => '1',
                    'round'          => (string) $matche->round,
                    'competition_id' => (string) $league->id,
                    'league_id'      => (string) $league->league_id,
                    'competition_en' => $league->translate('en')->name,
                    'competition_ar' => $league->translate('ar')->name,
                    'image'          => (string) ($league->image_path ?? ''),
                ];

                $this->topicNotifyByFirebaseTokens($client_tokens_en, [
                    'title'  => $league->translate('en')->name,
                    'body'   => 'The ' . $matche->round . ' of ' . $league->translate('en')->name . ' will start today',
                    'image'  => $league->image_path,
                    'notify' => $notifyPayload,
                ]);

                $this->topicNotifyByFirebaseTokens($client_tokens_ar, [
                    'title'  => $league->translate('ar')->name,
                    'body'   => 'انطلاق الاسبوع ' . $weekNumber . ' من ' . $league->translate('ar')->name . ' اليوم',
                    'image'  => $league->image_path,
                    'notify' => $notifyPayload,
                ]);
            }
        }

        $this->info('Finished sending legacy league notifications.');
    }
}