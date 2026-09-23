<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Models\Team;
use App\Models\Matche;
use App\Models\Country;
use App\Models\Setting;
use App\Models\Competition;
use Illuminate\Support\Str;
use App\Models\TeamTranslation;
use Illuminate\Console\Command;
use App\Models\MatcheTranslation;
use App\Models\CountryTranslation;
use Illuminate\Support\Facades\Http;
use App\Models\CompetitionTranslation;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Storage;

class getMatchesFromApiAndSaveOnDatabaseEvery15Days extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'save:matches';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get Data From Football Api and save it to database once every 15 days / EDITED: To once every day';

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
        $today = date('Y-m-d'); // today

        // $from = date('Y-m-d'); 

        // $date = Carbon::createFromFormat('Y-m-d', date('Y-m-d'));
        // $daysToAdd = 15;
        // $date = $date->addDays($daysToAdd);
        // $date = substr($date,0,10); // after 15 day

        // $to = $date;

         //leagues arraies start
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

         // leagues arraies end

        $response = Http::withHeaders([
            'x-rapidapi-key' => $setting->football_key
        ])->get('https://v3.football.api-sports.io/fixtures', [
            'timezone' => 'UTC',
            'date' => $today
        ]);

        $data = $response->json($key = "response");

        // New ForLoop Code For handling multi foreach
        if (!empty($data)) {
            
            $matchesInDatabase = Matche::whereDate('match_date',$today)->whereNotNull('fixture_id')->get()->pluck('fixture_id')->toArray();
            $teams = Team::whereNotNuLL('team_id')->get()->pluck('team_id')->toArray();
            foreach ($data as $data1) {
                $leagueId = $data1["league"]["id"];
                $fixtureId = $data1["fixture"]["id"];

                if (in_array($leagueId,$league)) {
                    // if ($setting->season == $data1["league"]["season"]) {


                    // Handling champions
                    if (!in_array($leagueId,$leagueInDatabase) && !in_array($leagueId,$checkedLeagueId)) {
                        $checkedLeagueId[] = $leagueId;

                        $country = CountryTranslation::where('name',$data1["league"]["country"])->first();
        
                        if( $country ){
                            $countryId = $country -> country_id;
                        } else{
        
                            $imageUrl = $data1["league"]["flag"];
                            $ext = pathinfo($imageUrl,PATHINFO_EXTENSION);
                            $imageContents = @file_get_contents($imageUrl);
                            $imageName = time() . Str::random(10) . "." . $ext;
                            Storage::disk('public')->put('uploads/country_images/' . $imageName , $imageContents);
        
                            $country = Country::create([
                                'en' => [
                                    'name' => $data1["league"]["country"]
                                ],
                                'ar' => [
                                    'name' => $data1["league"]["country"]
                                ],
                                'image' => $imageName
                            ]);
                            $countryId = $country -> id;
                        }
        
                        $imageUrl = $data1["league"]["logo"];
                        $ext = pathinfo($imageUrl,PATHINFO_EXTENSION);
                        $imageContents = @file_get_contents($imageUrl);
                        $imageName = time() . Str::random(10) . "." . $ext;
                        Storage::disk('public')->put('uploads/competition_images/' . $imageName , $imageContents);
        
                        $add = Competition::create([
                            'en' => [
                                'name' => $data1["league"]["name"]
                            ],
                            'ar' => [
                                'name' => $data1["league"]["name"]
                            ],
                            'league_id' => $leagueId,
                            'country_id' => $countryId,
                            'image' => $imageName ,
                            'season' => $data1["league"]["season"],
                        ]);
                        $id = $add -> id;
                    }

                    // Handling Teams

                    $homeTeamId = $data1["teams"]["home"]["id"];
                    $team = Team::where('team_id',$homeTeamId)->first();

                    if(!$team){
                        $team = Team::create([
                            'en' => [
                                'name' => $data1["teams"]["home"]["name"]
                            ],
                            'ar' => [
                                'name' => $data1["teams"]["home"]["name"]
                            ],
                            'team_id' => $data1["teams"]["home"]["id"],
                            'image' => $data1["teams"]["home"]["logo"],
                        ]);
                        $id = $team->id;
                    }

                    $awayTeamId = $data1["teams"]["away"]["id"];
                    $team = Team::where('team_id',$awayTeamId)->first();

                    if(!$team){
                        $team = Team::create([
                            'en' => [
                                'name' => $data1["teams"]["away"]["name"]
                            ],
                            'ar' => [
                                'name' => $data1["teams"]["away"]["name"]
                            ],
                            'team_id' => $data1["teams"]["away"]["id"],
                            'image' => $data1["teams"]["away"]["logo"],
                        ]);
                        $id = $team->id;

                    }
                    $dateTime = new \DateTime($data1["fixture"]["date"]);
                    
                    // Handling Matches
                    if (!in_array($fixtureId,$matchesInDatabase)) {
                        $tema1Id = $data1["teams"]["home"]["id"];
                        $tema2Id = $data1["teams"]["away"]["id"];
                        $leagueId = $data1["league"]["id"];
                        $week = $data1["league"]["round"];
                        $week = (int) filter_var($week, FILTER_SANITIZE_NUMBER_INT);
                        $week = abs($week);
        
                        $team1Id = Team::where('team_id',$tema1Id)->first()->id;
                        $team2Id = Team::where('team_id',$tema2Id)->first()->id;
                        $compId  = Competition::where('league_id',$leagueId)->first()->id;
        
                        $match = Matche::create([
                            'en' => [
                                'location' => $data1["fixture"]["venue"]["name"]
                            ],
                            'ar' => [
                                'location' => $data1["fixture"]["venue"]["name"]
                            ],
                        'fixture_id' => $fixtureId,
                        'team1_id' => $team1Id ,
                        'team2_id' => $team2Id,
                        'competition_id' => $compId,
                        'match_date' => $dateTime->format('Y-m-d H:i:s'),
                        'week' => $week,
                        'status' => $data1["fixture"]["status"]["short"],
                        ]);
                        $matchId = $match->id;
                    }




                // }
                }
            }

        }
    }
}
