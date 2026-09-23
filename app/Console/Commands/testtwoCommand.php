<?php

namespace App\Console\Commands;

use App\Models\Competition;
use App\Models\Country;
use App\Models\CountryTranslation;
use App\Models\Player;
use App\Models\Setting;
use App\Models\Team;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class testtwoCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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

        // $query = Competition::find(124);
        // $players = Player::whereIn('team_id', DB::table('competition_team')->where('competition_id', $query->id)->pluck('team_id'))->delete();
        // $teams = DB::table('competition_team')->where('competition_id', $query->id)->delete();
        // $query->delete();
        // $arr = [];
        for ($i = 17; $i <=50; $i++) {
            Team::find($i)->delete();
        }
        $this->info('Teams deleted successfully');
        // dd($arr);
    
        // $setting = Setting::find(1);
        // $a = $setting->leagues;
        // $league = explode(",",$a);

        // $response = Http::withHeaders([
        //     'x-rapidapi-key' => $setting->football_key
        // ])->get('https://v3.football.api-sports.io/leagues', [
        //     // 'timezone' => 'UTC',
        //     'season' => '2022'
        // ]);

        // $data = $response->json($key = "response");

        // foreach ($data as $data1){
        //     $leagueId = $data1["league"]["id"];
        //     if (in_array($leagueId,$league)) {
        //         // Countery Images
        //         // $country = CountryTranslation::where('name',$data1["country"]["name"])->first();
        //         // if ($country) {
        //         //     $countryId = $country->country_id;

        //         //     $cout = Country::find($countryId);
        //         //     if ($cout) {
        //         //         if (!filter_var($cout->image, FILTER_VALIDATE_URL)) {
        //         //             $imageUrl = $data1["country"]["flag"];
        //         //             $ext = pathinfo($imageUrl,PATHINFO_EXTENSION);
        //         //             $imageContents = @file_get_contents($imageUrl);
        //         //             $imageName = time() . Str::random(10) . "." . $ext;
        //         //             Storage::disk('public')->put('uploads/country_images/' . $imageName , $imageContents);
        //         //             $cout->image = $imageName;
        //         //             $cout->update();
        //         //         }
        //         //     }
        //         // }


        //         // Leagues Images
        //         $leagues = Competition::where('league_id', $leagueId)->first();
        //         if ($leagues) {
        //             if (!filter_var($leagues->image, FILTER_VALIDATE_URL)) {
        //                 $imageUrl = $data1["league"]["logo"];
        //                 $ext = pathinfo($imageUrl,PATHINFO_EXTENSION);
        //                 $imageContents = @file_get_contents($imageUrl);
        //                 $imageName = time() . Str::random(10) . "." . $ext;
        //                 Storage::disk('public')->put('uploads/competition_images/' . $imageName , $imageContents);
        //                 $leagues->image = $imageName;
        //                 $leagues->update();
        //             }
        //         }

        //     }
        // }
    }
}
