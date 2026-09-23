<?php

namespace App\Console\Commands;

use App\Models\Competition;
use App\Models\Matche;
use App\Models\Setting;
use App\Models\Team;
use App\Traits\NotificationOfMatchesTrait;
use App\Traits\Notify;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class testNotificationsCommand extends Command
{
    use Notify, NotificationOfMatchesTrait;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:noti';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'For test notifications that send to firebase';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // $clients = DB::table('clients')->pluck('fb_token');

        // $this->topicNotifyByFirebaseTokens($clients, [
        //     'title' => 'Firebase Notification',
        //     'body' => 'For test notifications that send to firebase from backend to all clients.',
        // ]);

        // return true

        // $dates = [];
        // $startDate = Carbon::createFromFormat('Y-m-d', date('Y-m-d', strtotime('+1 days')));
        // $endDate = Carbon::createFromFormat('Y-m-d', date('Y-m-d', strtotime('+20 days')));
        // $dateRange = CarbonPeriod::create($startDate, $endDate);
        //     foreach ($dateRange->toArray() as $date) {
        //         $dateTime = new \DateTime($date);
        //         $newDate = $dateTime->format('Y-m-d');
        //         \array_push($dates , $newDate);
        //     }

            $setting = Setting::find(1);
            $a = $setting->leagues;
            $league = explode(",",$a);
           //  $league = array(1, 39, 233, 78, 61, 140, 135, 186, 2, 387, 71, 402, 307, 128, 330, 5, 305, 417, 202, 200, 542, 204, 88, 253, 479, 301, 338, 309, 308, 887, 888, 889);
            $comp = Competition::get();
            foreach ($comp as $value) {
                if($value->league_id != Null){
                    $leagueInDatabase[] = $value->league_id;
   
                }
            }
            $checkedLeagueId = array();

// Saudi-Arabia  ,  Egypt
        foreach ($league as $today) {

            $response = Http::withHeaders([
                'x-rapidapi-key' => $setting->football_key
            ])->get('https://v3.football.api-sports.io/teams', [
                'season' => '2022',
                'league' => $today
            ]);
    
            $data = $response->json($key = "response");
            // $matchesInDatabase = Matche::whereDate('match_date',$today)->whereNotNull('fixture_id')->get()->pluck('fixture_id')->toArray();
            // $teams = Team::whereNotNuLL('team_id')->get()->pluck('team_id')->toArray();
            
            if (!empty($data)) {

                foreach ($data as $data1) {

                    if ($data1['team']['country'] == 'Saudi-Arabia') {
                        continue;
                    }

                    if ($data1['team']['country'] == 'Egypt') {
                        continue;
                    }

                    $homeTeamId = $data1["team"]["id"];
                    $team = Team::where('team_id',$homeTeamId)->first();
                    // if ($data1['league']['country'] == 'Egypt') {
                    
                    // $homeTeamId = $data1["teams"]["home"]["id"];
                    
                    if ($team) {
                            if (filter_var($team->image, FILTER_VALIDATE_URL)) {
                        continue;
                    }
                        if (File::exists(public_path('storage/uploads/team_images/'. $team->image))) {
                            continue;
                        }
                        $imageUrl = $data1["team"]['logo'];
                        $ext = pathinfo($imageUrl,PATHINFO_EXTENSION);
                        $imageContents = @file_get_contents($imageUrl);
                        $imageName = time() . Str::random(10) . "." . $ext;
                        Storage::disk('public')->put('uploads/team_images/' . $imageName , $imageContents);
                        $team->image = $imageName;
                        $team->update();
                    }

                }
            }
        }
    }
}
