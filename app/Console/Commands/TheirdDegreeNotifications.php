<?php

namespace App\Console\Commands;

use App\Models\Matche;
use App\Models\Team;
use App\Models\Client;
use App\Traits\Notify;
use App\Traits\NotificationOfMatchesTrait;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TheirdDegreeNotifications extends Command
{
    use Notify, NotificationOfMatchesTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'theird:send';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send notifications of the third degree matches';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(): int
    {
        try {
            $matches = Matche::query()
                ->whereNull('fixture_id')
                ->whereDate('match_date', Carbon::today())
                ->where('match_date', '>=', Carbon::now()->subMinutes(1))
                ->where('match_date', '<=', Carbon::now()->addMinutes(1))
                ->whereNotIn('status', ['PST', 'CANC', 'SUSP', 'ABD', 'AWD', 'WO'])
                ->with('competition.parent')
                ->get()
                ->each(function ($builder) {
                    $builder->home_team_name_ar = $builder->team1()->first()->translate('ar')->name;
                    $builder->home_team_name_en = $builder->team1()->first()->translate('en')->name;
                    $builder->away_team_name_ar = $builder->team2()->first()->translate('ar')->name;
                    $builder->away_team_name_en = $builder->team2()->first()->translate('en')->name;
                    $builder->home_goals        = 0;
                    $builder->away_goals        = 0;
                    $builder->home_image        = $builder->team1()->first()->image_path;
                    $builder->away_image        = $builder->team2()->first()->image_path;
                });

            if ($matches->isEmpty()) {
                $this->info('No matches found.');
                return 0;  // ← was return true
            }

            foreach ($matches as $match) {
                try {
                    if ($this->checkEventSent(0, 'match-started', $match->team1_id, $match->id) == false) {
                        continue;
                    }
                    if ($this->checkEventSent(0, 'match-started', $match->team2_id, $match->id) == false) {
                        continue;
                    }

                    $homeTeam = Team::find($match->team1_id);
                    $awayTeam = Team::find($match->team2_id);
                    $this->storeEvent(0, 'match-started', null, null, $homeTeam, $match->id);
                    $this->storeEvent(0, 'match-started', null, null, $awayTeam, $match->id);

                    $notifyPayload = [
                        'match_id'     => (string) $match->id,
                        'fixture_id'   => (string) ($match->fixture_id ?? ''),
                        'home_team_id' => (string) $match->team1_id,
                        'away_team_id' => (string) $match->team2_id,
                    ];

                    $teams = collect([$match->team1_id, $match->team2_id]);
                    $clientsGotNotification = [];

                    foreach ($teams as $team) {
                        $client_ids = DB::table('favourite_team')
                            ->where('team_id', $team)
                            ->pluck('client_id')
                            ->toArray();

                        $clientsNotYetNotified  = array_diff($client_ids, $clientsGotNotification);
                        $clientsGotNotification = array_merge($clientsGotNotification, $clientsNotYetNotified);

                        $client_tokens_en = DB::table('clients')
                            ->where('locale', 'en')
                            ->whereIn('id', $clientsNotYetNotified)
                            ->whereNotNull('fb_token')
                            ->pluck('fb_token')
                            ->toArray();

                        $client_tokens_ar = DB::table('clients')
                            ->where('locale', 'ar')
                            ->whereIn('id', $clientsNotYetNotified)
                            ->whereNotNull('fb_token')
                            ->pluck('fb_token')
                            ->toArray();

                        $this->topicNotifyByFirebaseTokens($client_tokens_en, [
                            'title'  => $match->home_team_name_en . " vs " . $match->away_team_name_en,
                            'body'   => 'Match Started',
                            'image'  => $this->mergeImagesInOneImage($match->away_image, $match->home_image),
                            'notify' => $notifyPayload,
                        ]);

                        $this->topicNotifyByFirebaseTokens($client_tokens_ar, [
                            'title'  => $match->home_team_name_ar . " ضد " . $match->away_team_name_ar,
                            'body'   => 'بداية المباراة',
                            'image'  => $this->mergeImagesInOneImage($match->away_image, $match->home_image),
                            'notify' => $notifyPayload,
                        ]);
                    }

                } catch (\Exception $e) {
                    Log::error('theird:send match failed', [
                        'match_id' => $match->id ?? null,
                        'error'    => $e->getMessage(),
                    ]);
                }
            }

            $this->info('Notifications sent successfully.');

        } catch (\Exception $e) {
            Log::error('theird:send command failed: ' . $e->getMessage());
        }

        return 0;  // ← always return int 0
    }
}