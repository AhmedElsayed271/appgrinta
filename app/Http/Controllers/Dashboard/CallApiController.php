<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Competition;
use App\Models\Player;
use App\Models\Setting;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class CallApiController extends Controller
{
    public function index(){
        $league= \request()->input('league');
        $total=$this->playersWithLeague(1,$league);
        if (is_numeric($total))
        {
            for ($i=2;$i<=$total;$i++){
                $this->playersWithLeague($i,$league);
            }
        }
    }
    public function teamsWithLeague(){
        $league= \request()->input('league');
        $competition=Competition::query()->where('league_id',$league)->first();
        $this->getTeams($league,$competition);
    }
    public function playerWithTeams(){
        $team= \request()->input('team');
        $total=$this->playersWithTeam(1,$team);
        if (is_numeric($total))
        {
            for ($i=2;$i<=$total;$i++){
                $this->playersWithTeam(1,$team);
            }
        }
    }
    public function add_football_team_id(){
        $teams=Team::query()->whereNotNull('team_id')->get()->pluck('team_id','id')->toArray();
        foreach ($teams as $key=>$value){
            DB::table('players')->where('team_id',$key)->update([
                'football_team_id'=>$value
            ]);
        }
    }

    protected function playersWithLeague(int $page,$league){
        $setting = Setting::find(1);

        $response = Http::withHeaders([
                 'x-rapidapi-key' => $setting->football_key
        ])->get('https://v3.football.api-sports.io/players?league='.(string)$league.'&season=' . $setting->season . '&page='.$page)->json();
        if (is_array($response['response'])){
            foreach ($response['response'] as $item){
                $statistics=$item['statistics'];
                $team=$statistics[0]['team'];
                //create team
                $checkTeamExist=Team::query()->where('team_id',$team['id'])->first();
                $checkTeam=null;
                if ($checkTeamExist==null){
                    $checkTeam=Team::query()->create([
                        'en' => ['name' => $team['name']],
                        'ar' => ['name' => $team['name'] ],
                        'team_id'=>$team['id'],
                        'image'=>$team['logo'],
                    ]);
                }else{
                    $checkTeam=$checkTeamExist;
                }
                $player=$item['player'];
                $statistics=$item['statistics'][0];
                $games=$statistics['games'];
                $position=$games['position'];
                $checkPlayer=Player::query()->where('player_id',$player['id'])->first();
                if ($checkPlayer==null){
                    Player::query()->create([
                        'en' => [
                            'first_name' => $player['firstname'],
                            'last_name' => $player['lastname'],
                        ],
                        'ar' => [
                            'first_name' => $player['firstname'],
                            'last_name' => $player['lastname'],
                        ],
                        'player_id'=>$player['id'],
                        'team_id'=>$checkTeam->id,
                        'football_team_id'=>$team['id'],
                        'current_value'=>'default value',
                        'position'=>$position,
                        'twitter_account'=>'https://twitter.com/',
                        'image'=>$player['photo'],
                    ]);
                }
            }
        }
        return $response['paging']['total'];
    }
    protected function playersWithTeam(int $page,$team){
        
        $setting = Setting::find(1);

        $response = Http::withHeaders([
                 'x-rapidapi-key' => $setting->football_key
        ])->get('https://v3.football.api-sports.io/players?team='.(string)$team.'&season=' . $setting->season .'&page='.$page)->json();
        if (is_array($response['response'])){
            foreach ($response['response'] as $item){
                $statistics=$item['statistics'];
                $team=$statistics[0]['team'];
                //create team
                $checkTeamExist=Team::query()->where('team_id',$team['id'])->first();
                $checkTeam=null;
                if ($checkTeamExist==null){
                    $checkTeam=Team::query()->create([
                        'en' => ['name' => $team['name']],
                        'ar' => ['name' => $team['name'] ],
                        'team_id'=>$team['id'],
                        'image'=>$team['logo'],
                    ]);
                }else{
                    $checkTeam=$checkTeamExist;
                }
                $player=$item['player'];
                $statistics=$item['statistics'][0];
                $games=$statistics['games'];
                $position=$games['position'];
                $checkPlayer=Player::query()->where('player_id',$player['id'])->first();
                if ($checkPlayer==null){
                    Player::query()->create([
                        'en' => [
                            'first_name' => $player['firstname'],
                            'last_name' => $player['lastname'],
                        ],
                        'ar' => [
                            'first_name' => $player['firstname'],
                            'last_name' => $player['lastname'],
                        ],
                        'player_id'=>$player['id'],
                        'team_id'=>$checkTeam->id,
                        'football_team_id'=>$team['id'],
                        'current_value'=>'default value',
                        'position'=>$position,
                        'twitter_account'=>'https://twitter.com/',
                        'image'=>$player['photo'],
                    ]);
                }
            }
        }
        return $response['paging']['total'];
    }

    protected function getTeams($league,Competition $competition){
        
        $setting = Setting::find(1);

        $response = Http::withHeaders([
                 'x-rapidapi-key' => $setting->football_key
        ])->get('https://v3.football.api-sports.io/teams?league='.(string)$league.'&season=' . $setting->season .'&')->json();

        if (is_array($response['response'])) {
            foreach ($response['response'] as $item) {
                $team=$item['team'];
                $checkTeam=null;
                $checkTeamExist=Team::query()->where('team_id',$team['id'])->first();
                if ($checkTeamExist==null){
                    $checkTeam=Team::query()->create([
                        'en' => ['name' => $team['name']],
                        'ar' => ['name' => $team['name'] ],
                        'team_id'=>$team['id'],
                        'image'=>$team['logo'],
                    ]);
                }else{
                    $checkTeam=$checkTeamExist;
                }
                $competition->teams()->syncWithoutDetaching($checkTeam->id);
            }
        }
    }
}
