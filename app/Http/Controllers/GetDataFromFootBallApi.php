<?php

namespace App\Http\Controllers;

use DateTime;
use Carbon\Carbon;
use App\Models\Team;
use App\Models\Matche;
use App\Models\Player;
use App\Models\Setting;
use App\Models\testtest;
use App\Models\MatchEvent;
use App\Models\Competition;
use Illuminate\Http\Request;
use App\Models\TeamTranslation;
use App\Models\MatcheTranslation;
use App\Models\PlayerTranslation;
use Illuminate\Support\Facades\DB;
use App\Models\FirstMatchsOfRounds;
use Illuminate\Support\Facades\Http;
use App\Http\Resources\MatchResource;
use Illuminate\Support\Facades\Storage;

use App\Models\MatchEventTranslation;
use App\Models\CompetitionTranslation;
use function PHPUnit\Framework\isNull;
use App\Models\FirstMatchesOfRounds;
use App\Traits\Notify;

class GetDataFromFootBallApi extends Controller
{
    use Notify;


    public function getMatchesFromTo()
    {
        $setting = Setting::find(1);

        // $from = date('Y-m-d'); // today
        $from = "2022-11-20"; // today

        $date = Carbon::createFromFormat('Y-m-d', date('Y-m-d'));
        $daysToAdd = 15;
        $date = $date->addDays($daysToAdd);
        $date = substr($date, 0, 10); // after 15 day

        // $to = $date;
        $to = "2022-12-17";

        $leagueInDatabase = array();
        $teamsInDatabase = array();
        //leagues arraies start
        $setting = Setting::find(1);
        $a = $setting->leagues;
        $league = explode(",",$a);
        // $league = array(1, 39, 233, 78, 61, 140, 135, 186, 2, 387, 71, 402, 307, 128, 330, 5, 305, 417, 202, 200, 542, 204, 88, 253, 479, 301, 338, 309, 308, 887, 888, 889);
        $comp = Competition::get();
        foreach ($comp as $value) {
            if ($value->league_id != Null) {
                $leagueInDatabase[] = $value->league_id;
            }
        }
        $checkedLeagueId = array();

        // leagues arraies end

        // teams arraies start
        $teams = Team::get();
        foreach ($teams as $team) {
            if ($team->team_id != Null) {
                $teamsInDatabase[] = $team->team_id;
            }
        }

        // teams arraies end

        foreach ($league as $trueData) {
            $response = Http::withHeaders([
                 'x-rapidapi-key' => $setting->football_key
                //$setting->apikey
                //'9b9e63115b02a50d22179fb4f1f1b518'
            ])->get('https://v3.football.api-sports.io/fixtures', [
               'timezone' => 'UTC',
                'league' => $trueData,
                'from' => $from,
                'to' => $to,
                'season' => $setting->season,
            ]);

            $data = $response->json($key = "response");
            // add new competition to database
            foreach ($data as $data1) {
                $leagueId = $data1["league"]["id"];
                if (in_array($leagueId, $league) && !in_array($leagueId, $leagueInDatabase) && !in_array($leagueId, $checkedLeagueId)) { // check if array is in our legue array list and not on database and get unique ids only
                    $checkedLeagueId[] = $leagueId;
                    $add = Competition::create([
                        'league_id' => $leagueId,
                        'image' => $data1["league"]["logo"],
                        'season' => $data1["league"]["season"],
                    ]);
                    $id = $add->id;
                    CompetitionTranslation::create([
                        'competition_id' => $id,
                        'name' => $data1["league"]["name"],
                        'locale' => 'en',
                    ]);
                    CompetitionTranslation::create([
                        'competition_id' => $id,
                        'name' => $data1["league"]["name"],
                        'locale' => 'ar',
                    ]);
                }
            }
            // add new teams

            foreach ($data as $data1) {

                $leagueId = $data1["league"]["id"];

                if (in_array($leagueId, $league)) {


                    $homeTeamId = $data1["teams"]["home"]["id"];

                    if (!in_array($homeTeamId, $teamsInDatabase)) {
                        $team = Team::create([
                            'team_id' => $data1["teams"]["home"]["id"],
                            'image' => $data1["teams"]["home"]["logo"],
                        ]);
                        $id = $team->id;
                        TeamTranslation::create([
                            'team_id' => $id,
                            'name' => $data1["teams"]["home"]["name"],
                            'locale' => 'en',
                        ]);
                        TeamTranslation::create([
                            'team_id' => $id,
                            'name' => $data1["teams"]["home"]["name"],
                            'locale' => 'ar',
                        ]);
                    }

                    $awayTeamId = $data1["teams"]["away"]["id"];

                    if (!in_array($awayTeamId, $teamsInDatabase)) {
                        $team = Team::create([
                            'team_id' => $data1["teams"]["away"]["id"],
                            'image' => $data1["teams"]["away"]["logo"],
                        ]);
                        $id = $team->id;
                        TeamTranslation::create([
                            'team_id' => $id,
                            'name' => $data1["teams"]["away"]["name"],
                            'locale' => 'en',
                        ]);
                        TeamTranslation::create([
                            'team_id' => $id,
                            'name' => $data1["teams"]["away"]["name"],
                            'locale' => 'ar',
                        ]);
                    }
                }
            }

            ///////////// Save matches in database and check to be unique start /////////////////////////
            $matchesInDatabase = Matche::whereBetween('match_date', [$from, $to])->whereNotNull('fixture_id')->get()->pluck('fixture_id')->toArray();

            foreach ($data as $data1) {

                $leagueId = $data1["league"]["id"];
                $fixtureId = $data1["fixture"]["id"];
                if (in_array($leagueId, $league) && !in_array($fixtureId, $matchesInDatabase)) {

                    $tema1Id = $data1["teams"]["home"]["id"];
                    $tema2Id = $data1["teams"]["away"]["id"];
                    $leagueId = $data1["league"]["id"];
                    $week = $data1["league"]["round"];
                    $week = (int) filter_var($week, FILTER_SANITIZE_NUMBER_INT);
                    $week = abs($week);

                    $team1Id = Team::where('team_id', $tema1Id)->first()->id;
                    $team2Id = Team::where('team_id', $tema2Id)->first()->id;
                    $compId  = Competition::where('league_id', $leagueId)->first()->id;

                    $match = Matche::create([
                        'fixture_id' => $fixtureId,
                        'team1_id' => $team1Id,
                        'team2_id' => $team2Id,
                        'competition_id' => $compId,
                        'match_date' => $data1["fixture"]["date"],
                        'week' => $week,
                        'status' => $data1["fixture"]["status"]["short"],
                    ]);
                    $matchId = $match->id;
                    MatcheTranslation::create([
                        'matche_id' => $matchId,
                        'location' => $data1["fixture"]["venue"]["name"],
                        'locale' => 'en'
                    ]);
                    MatcheTranslation::create([
                        'matche_id' => $matchId,
                        'location' => $data1["fixture"]["venue"]["name"],
                        'locale' => 'ar'
                    ]);
                }
            }
        }

        return "success";
    }

    // update match status and minutes and goal
    public function updateMatchesStatusWithData()
    {
        $kontrol = testtest::find(1);
        if ($kontrol->enable == 1) {


            // leagues wich we will work with
            $setting = Setting::find(1);
            $a = $setting->leagues;
            $league = explode(",",$a);
            // $league = array(1, 39, 233, 78, 61, 140, 135, 186, 2, 387, 71, 402, 307, 128, 330, 5, 305, 417, 202, 200, 542, 204, 88, 253, 479, 301, 338, 309, 308);

            // get matches in date
            $response = Http::withHeaders([
                     'x-rapidapi-key' => $setting->football_key
            ])->get('https://v3.football.api-sports.io/fixtures', [
                // 'timezone' => $timezone,
                // 'date' => $date,
                'timezone' => 'UTC',
                'date' => date("Y-m-d"),
            ]);

            $data = $response->json($key = "response"); // get response
            // update the data of all matches in the date
            foreach ($data as $data1) {
                $leagueId = $data1["league"]["id"];
                if (in_array($leagueId, $league)) {

                    $match = Matche::where('fixture_id', $data1["fixture"]["id"])->first();

                    if ($match) {
                        $matchId = $match->id;
                        $a = $match->update([
                            'status' => $data1["fixture"]["status"]["short"],   // update status
                            'elapsed' => $data1["fixture"]["status"]["elapsed"] // update elsapsed

                        ]);
                        if ($a) {
                            return "ok" . $data1["fixture"]["status"]["elapsed"];
                        }

                        $fixtureId = $data1["fixture"]["id"];

                        // get match events (goals)
                        $request = Http::withHeaders([
                               'x-rapidapi-key' => $setting->football_key
                        ])->get('https://v3.football.api-sports.io/fixtures/events', [
                            'fixture' => $fixtureId,
                        ]);

                        $responeData = $request->json($key = "response");
                        $matchEventsMinutesArray = array();
                        $matchEventsDatabase = MatchEvent::where('match_id', $matchId)->get(['minute']);
                        foreach ($matchEventsDatabase as $matchEventsDatabase1) {
                            $matchEventsMinutesArray[] = $matchEventsDatabase1->minute; // we will use this array to check if event is exist or not
                        }

                        foreach ($responeData as $responeData1) {
                            $type = strtolower($responeData1["type"]);
                            if ($type != "subst" && !in_array($responeData1["time"]["elapsed"], $matchEventsMinutesArray)) { // get only goals and events wich doesn't exists

                                $team = Team::where('team_id', $responeData1["team"]["id"])->first(); // get team id
                                if ($team) {
                                    $teamId = $team->id;
                                } else {
                                    $team = Team::create([
                                        'team_id' => $responeData1["team"]["id"],
                                        'image' => $responeData1["team"]["logo"],
                                    ]);
                                    $teamId = $team->id;
                                }
                                $player = Player::where('player_id', $responeData1["player"]["id"])->first(); // get player
                                if ($player) {
                                    $playerId = $player->id;
                                } else {
                                    $player = Player::create([
                                        'player_id' => $responeData1["player"]["id"],
                                        'team_id' => $teamId,
                                        'image' => 'https://media.api-sports.io/football/players/' . $responeData1["player"]["id"] . '.png',
                                        'football_team_id' => $responeData1["team"]["id"]
                                    ]);
                                    $playerId = $player->id;
                                }

                                // add event to our database
                                if ($type == "card") {
                                    $type = strtolower($responeData1["detail"]);
                                }
                                $matchEvent = MatchEvent::create([
                                    'minute' => $responeData1["time"]["elapsed"],
                                    'status' => $type,
                                    'player_name' => $responeData1["player"]["name"],
                                    'player_id' => $playerId,
                                    'team_id' => $teamId,
                                    'match_id' => $matchId,

                                ]);
                                $matchEventId = $matchEvent->id;
                                // add event translation to our database
                                MatchEventTranslation::create([
                                    'match_event_id' => $matchEventId,
                                    'description' => $type,
                                    'locale' => 'en',
                                ]);
                            }
                        }
                    }
                }
            }
        }
    }

    public function getMatchesEvents()
    {
        $from = "2022-11-20";

        $to = "2022-12-17";

        $matches = Matche::whereBetween('match_date', [$from, $to])->whereNotNull('fixture_id')->get()->pluck('fixture_id')->toArray();

        foreach ($matches as $fixtureId) {
            $matchId = Matche::where('fixture_id', $fixtureId)->first()->id;

            $request = Http::withHeaders([
                 'x-rapidapi-key' => $setting->football_key
            ])->get('https://v3.football.api-sports.io/fixtures/events', [
                'fixture' => $fixtureId,
            ]);
            $responeData = $request->json($key = "response");

            foreach ($responeData as $responeData1) {
                $type = strtolower($responeData1["type"]);
                if ($type != "subst") { // get only goals and events wich doesn't exists

                    $team = Team::where('team_id', $responeData1["team"]["id"])->first(); // get team id
                    if ($team) {
                        $teamId = $team->id;
                    } else {
                        $team = Team::create([
                            'team_id' => $responeData1["team"]["id"],
                            'image' => $responeData1["team"]["logo"],
                        ]);

                        $teamId = $team->id;
                        TeamTranslation::create([
                            'team_id' => $teamId,
                            'name' => $responeData1["team"]["name"],
                            'locale' => "en",
                        ]);
                        TeamTranslation::create([
                            'team_id' => $teamId,
                            'name' => $responeData1["team"]["name"],
                            'locale' => "ar",
                        ]);
                    }
                    $player = Player::where('player_id', $responeData1["player"]["id"])->first(); // get player
                    if ($player) {
                        $playerId = $player->id;
                    } else {
                        $player = Player::create([
                            'player_id' => $responeData1["player"]["id"],
                            'team_id' => $teamId,
                            'image' => 'https://media.api-sports.io/football/players/' . $responeData1["player"]["id"] . '.png',
                            'football_team_id' => $responeData1["team"]["id"]
                        ]);
                        $playerId = $player->id;

                        $player2 = explode(' ', $responeData1["player"]["name"]);

                        if (count($player2) > 1) {
                            PlayerTranslation::create([
                                'player_id' => $playerId,
                                'first_name' => $player2[0],
                                'last_name' => $player2[1],
                                'locale' => "en",
                            ]);
                            PlayerTranslation::create([
                                'player_id' => $playerId,
                                'first_name' => $player2[0],
                                'last_name' => $player2[1],
                                'locale' => "ar",
                            ]);
                        } else {
                            PlayerTranslation::create([
                                'player_id' => $playerId,
                                'first_name' => $player2[0],
                                'locale' => "en",
                            ]);
                            PlayerTranslation::create([
                                'player_id' => $playerId,
                                'first_name' => $player2[0],
                                'locale' => "ar",
                            ]);
                        }
                    }

                    // add event to our database
                    if ($type == "card") {
                        $type = strtolower($responeData1["detail"]);
                    }
                    $matchEvent = MatchEvent::create([
                        'minute' => $responeData1["time"]["elapsed"],
                        'status' => $type,
                        'player_name' => $responeData1["player"]["name"],
                        'player_id' => $playerId,
                        'team_id' => $teamId,
                        'match_id' => $matchId,

                    ]);
                    $matchEventId = $matchEvent->id;
                    // add event translation to our database
                    MatchEventTranslation::create([
                        'match_event_id' => $matchEventId,
                        'description' => $type,
                        'locale' => 'en',
                    ]);

                    MatchEventTranslation::create([
                        'match_event_id' => $matchEventId,
                        'description' => $type,
                        'locale' => 'ar',
                    ]);
                }
            }
        }
    }

    public function getBestPlayers($leagueId)
    {

        $leagueMatches = Matche::where('competition_id', $leagueId)->get();
        $leagueMatchesId = array();

        foreach ($leagueMatches as $value) {
            $leagueMatchesId[] = $value->id;
        }

        $leagueBestPlayers =  MatchEvent::whereIn('match_id', $leagueMatchesId)
            ->where('status', 'goal')
            ->whereNotNull('player_id')
            ->select('player_id', DB::raw('count(*) as total'))
            ->groupBy('player_id')
            ->orderBy('total', 'DESC')
            ->take(20)
            ->with('player')
            ->get();

        $allPlayersData = array();
        foreach ($leagueBestPlayers as $value) {
            $team = Team::where('id', $value["player"]["team_id"])->first();
            $onePlayerData = array();
            $onePlayerData["playerName"] = $value["player"]["first_name"] . " " . $value["player"]["last_name"];
            $onePlayerData["playerImage"] = $value["player"]["image_path"];
            $onePlayerData["teamName"] = $team->name;
            $onePlayerData["teamImage"] = asset('storage/uploads/team_images/' . $team->image);
            $onePlayerData["totalGoals"] = $value["total"];
            $allPlayersData[] = $onePlayerData;
        }


        return response()->json([
            'leagueBestPlayers' => $allPlayersData,

        ]);
    }

    public function startUpdateMatchesEvents()
    {
        $test = testtest::find(1);
        $test->update([
            'enable' => 1
        ]);
        return back();
    }

    public function stopUpdateMatchesEvents()
    {
        $test = testtest::find(1);
        $test->update([
            'enable' => 0
        ]);
        return back();
    }

    public function updateInMinutes($minute)
    {
        $test = testtest::find(1);
        $test->update([
            'minute' => $minute
        ]);
        return back();
    }

    public function getbefore45()
    {


        $Now = Carbon::createFromFormat('Y-m-d H:i', date('Y-m-d H:i'));
        $MinutesToAdd = 45;
        $after45Minutes = $Now->addMinutes($MinutesToAdd);

        $matches = Matche::where('match_date', '>=', $after45Minutes)->get();


        return $matches;
    }

    public function sendNotificationFirstOneFinished()
    {

        $Now = Carbon::createFromFormat('H:i', date('H:i'))->tz('Europe/Istanbul');
        $MinutesToAdd = 45;
        $after45Minutes = $Now->addMinutes($MinutesToAdd)->format('H:i');

        $matches = Matche::query()->whereDate('match_date', Carbon::now()->format('Y-m-d'))->whereTime('match_date', $after45Minutes)->with('competition.parent')->get()->each(function ($builder) {
            $builder->home_goals = $builder->events()->getQuery()->where('status', 'goal')->where('team_id', $builder->team1_id)->get()->count();
            $builder->away_goals = $builder->events()->getQuery()->where('status', 'goal')->where('team_id', $builder->team2_id)->get()->count();
        });
        $teams = collect();
        $matches->pluck('team1_id')->each(function ($item, $key) use ($teams) {
            $teams->push($item);
        });
        $matches->pluck('team2_id')->each(function ($item, $key) use ($teams) {
            $teams->push($item);
        });

        // return Carbon::now()->tz('Europe/Istanbul')->format('H:i');
        // return $teams;
        foreach ($teams as $team) {
            $client_ids = DB::table('favourite_team')->where('team_id', $team)->get()->pluck('client_id')->toArray();
            $client_tokens_en = DB::table('clients')->where('locale', 'en')->whereIn('id', $client_ids)->get()->pluck('fb_token');
            $client_tokens_ar = DB::table('clients')->where('locale', 'ar')->whereIn('id', $client_ids)->get()->pluck('fb_token');
            $getTeam = Team::query()->find($team);
            $match = Matche::query()->whereDate('match_date', Carbon::now()->format('Y-m-d'))->whereTime('match_date', $after45Minutes)->whereIn('team1_id', [$team])->orWhereIn('team2_id', [$team])->first();
            $this->topicNotifyByFirebaseTokens($client_tokens_en, [
                'title' => $getTeam->translate('en')->name,
                'body' => 'Your Favourite team ' . $getTeam->translate('en')->name . 'has match after 45 minutes',
                'image' => $getTeam->image_path,
                'notify' => (object) array('match' => new MatchResource($match))
            ]);
            $this->topicNotifyByFirebaseTokens($client_tokens_ar, [
                'title' => $getTeam->translate('ar')->name,
                'body' => 'Your Favourite team ' . $getTeam->translate('ar')->name . 'has match after 45 minutes',
                'image' => $getTeam->image_path,
                'notify' => (object) array('match' => new MatchResource($match))
            ]);
        }
        return 0;
    }
    
    public function sendNotifLineup()
    {
        $Now = Carbon::createFromFormat('H:i', date('H:i'))->tz('Europe/Istanbul');
        $MinutesToAdd = 40;
        $after40Minutes = $Now->addMinutes($MinutesToAdd)->format('H:i');

        $matches = Matche::query()->whereDate('match_date', Carbon::now()->format('Y-m-d'))->whereTime('match_date', $after40Minutes)->with('competition.parent')->get()->each(function ($builder) {
            $builder->home_goals = $builder->events()->getQuery()->where('status', 'goal')->where('team_id', $builder->team1_id)->get()->count();
            $builder->away_goals = $builder->events()->getQuery()->where('status', 'goal')->where('team_id', $builder->team2_id)->get()->count();
        });
        $teams = collect();
        $matches->pluck('team1_id')->each(function ($item, $key) use ($teams) {
            $teams->push($item);
        });
        $matches->pluck('team2_id')->each(function ($item, $key) use ($teams) {
            $teams->push($item);
        });
        foreach ($matches as $match){
            $fixid = $getMatch->fixture_id;
            $response = Http::withHeaders([
                 'x-rapidapi-key' => $setting->football_key
            ])->get('https://v3.football.api-sports.io/lineups', [
                'fixture' =>$fixid,
            ]);
            $data = $response->json($key = "response"); // get response
            $result_count = $data["results"];
            if ($result_count != null) {
                foreach ($teams as $team) {
            $client_ids = DB::table('favourite_team')->where('team_id', $team)->get()->pluck('client_id')->toArray();
            $client_tokens_en = DB::table('clients')->where('locale', 'en')->whereIn('id', $client_ids)->get()->pluck('fb_token');
            $client_tokens_ar = DB::table('clients')->where('locale', 'ar')->whereIn('id', $client_ids)->get()->pluck('fb_token');
            $getTeam = Team::query()->find($team);
            $match = Matche::query()->whereDate('match_date', Carbon::now()->format('Y-m-d'))->whereTime('match_date', $after45Minutes)->whereIn('team1_id', [$team])->orWhereIn('team2_id', [$team])->first();
            $this->topicNotifyByFirebaseTokens($client_tokens_en, [
                'title' => $getTeam->translate('en')->name,
                'body' => 'lineup of ' . $getTeam->translate('en')->name . 'is available',
                'image' => $getTeam->image_path,
                'notify' => (object) array('match' => new MatchResource($match))
            ]);
            $this->topicNotifyByFirebaseTokens($client_tokens_ar, [
                'title' => $getTeam->translate('ar')->name,
                'body' => 'lineup of ' . $getTeam->translate('ar')->name . 'is available',
                'image' => $getTeam->image_path,
                'notify' => (object) array('match' => new MatchResource($match))
            ]);
        }
        return 0;
            }
        }

    }

    public function setAr()
    {
        $teams = DB::table('team_translations')->where('id', '>', 1852)->get();
        // $teams = DB::table('team_translations')->where('id','>',1852)->count();
        foreach ($teams as $team) {
            TeamTranslation::create([
                'team_id' => $team->team_id,
                'name' => $team->name,
                'locale' => 'ar',
            ]);
        }
        // return $teams;
    }

    public function getFirstMatchInLeague()
    {

        $setting = Setting::find(1);

        $leagues = array(301, 338, 309, 308);

        foreach ($leagues as $league) {

            $roundsRequest = Http::withHeaders([
                 'x-rapidapi-key' => $setting->football_key
            ])->get('https://v3.football.api-sports.io/fixtures/rounds', [
                'league' => $league,
                'season' => $setting->season,
            ]);

            $rounds = $roundsRequest->json("response");

            foreach ($rounds as $round) {

                $matchesRequest = Http::withHeaders([
                    'x-rapidapi-key' => $setting->football_key
                ])->get('https://v3.football.api-sports.io/fixtures', [
                    'timezone' => 'UTC',
                    'league' => $league,
                    'season' => $setting->season,
                    'round' => $round,
                ]);

                $sonuc = $matchesRequest->json("response");
                $coll = collect($sonuc);
                $sortedData = $coll->sortBy('fixture.date');
                $firstMatchOfRound = $sortedData->first();

                $leagueDatabase = Competition::where('league_id', $league)->first();
                if ($leagueDatabase) {
                    $leagueId = $leagueDatabase->id;
                } else {
                    $competition = Competition::create([
                        'league_id' =>  $league,
                        'image' => $firstMatchOfRound["league"]["logo"]
                    ]);
                    $leagueId = $competition->id;
                    CompetitionTranslation::create([
                        'competition_id' => $leagueId,
                        'name' => $firstMatchOfRound["league"]["name"],
                        'locale' => 'en'
                    ]);
                    CompetitionTranslation::create([
                        'competition_id' => $leagueId,
                        'name' => $firstMatchOfRound["league"]["name"],
                        'locale' => 'ar'
                    ]);
                }
                $matchDate = substr($firstMatchOfRound["fixture"]["date"], 0, 10);
                FirstMatchsOfRounds::create([
                    'competition_id' => $leagueId,
                    'fixture_id' => $firstMatchOfRound["fixture"]["id"],
                    'round' =>  $round,
                    'match_date' => $matchDate
                ]);
            }
        }
        return "success";
    }




    public function dene()
    {
               
        $request = Http::withHeaders([
            'x-rapidapi-key' => '9b9e63115b02a50d22179fb4f1f1b518'
        ])->get('https://v3.football.api-sports.io/fixtures/events', [
            'fixture' => '868375',
        ]);

        $responeData = $request->json($key = "response");
        $coll = collect($responeData);
        $importantEvents = $coll->where('type', '!=', 'subst');

        $matches = Matche::query()->where('fixture_id', '868375')->with('competition.parent')->get()->each(function ($builder) {
            $builder->home_team_name_ar = $builder->team1()->first()->translate('ar')->name;
            $builder->home_team_name_en = $builder->team1()->first()->translate('en')->name;
            $builder->away_team_name_ar = $builder->team2()->first()->translate('ar')->name;
            $builder->away_team_name_en = $builder->team2()->first()->translate('en')->name;
            $builder->home_goals = $builder->events()->getQuery()->where('status', 'goal')->where('team_id', $builder->team1_id)->get()->count();
            $builder->away_goals = $builder->events()->getQuery()->where('status', 'goal')->where('team_id', $builder->team2_id)->get()->count();
        });

        foreach ($importantEvents as $event) {
            $type = strtolower($event["type"]);
            $detail = strtolower($event["detail"]);

            if ($type == "yellow card") {
                $NotificationBodyAr = $matches[0]["away_goals"] . "-" . $matches[0]["home_goals"] . "\n\r '" .  $event["time"]["elapsed"] . "   " . "حصل اللاعب "  . " على كرت أصفر";
                $NotificationBodyEn = $matches[0]["home_goals"] . "-" . $matches[0]["away_goals"] . "\n\r '" .  $event["time"]["elapsed"] . "   " . " took " . $type;

                                       

            } else if ($type == "red card") {
                $NotificationBodyAr = $matches[0]["away_goals"] . "-" . $matches[0]["home_goals"] . "\n\r '" .  $event["time"]["elapsed"] . "   " . "حصل اللاعب " . $playerNameAr . " على كرت أحمر";
                $NotificationBodyEn = $matches[0]["home_goals"] . "-" . $matches[0]["away_goals"] . "\n\r '" .  $event["time"]["elapsed"] . "   " . $playerNameEn . " took " . $type;

                if($playerNameEn == "Isaac Lihadji"){
                    $NotificationBodyAr = $matches[0]["away_goals"] . "-" . $matches[0]["home_goals"] . "\n\r '" .  $event["time"]["elapsed"] . "   " . " حصل لاعب من فريق " . " على كرت أصفر ";
                    $NotificationBodyEn = $matches[0]["home_goals"] . "-" . $matches[0]["away_goals"] . "\n\r '" .  $event["time"]["elapsed"] . "   " . " One player from " .  " team took " . $type;
                }
            } else if ($type == "goal") {
                $NotificationBodyEn = $matches[0]["home_goals"] . "-" . $matches[0]["away_goals"] . "\n\r '" .  $event["time"]["elapsed"] . "   "  . " scored goal for " .  " team";
                $NotificationBodyAr = "ok";
                                  

            } else if ($type == "var") {
                
                if($detail == "goal cancelled"){
                    $NotificationBodyEn = $matches[0]["home_goals"] . "-" . $matches[0]["away_goals"] . "\n\r '" .  $event["time"]["elapsed"] . "   " . "[Var]Goal Cancelled";
                    $NotificationBodyAr = $matches[0]["away_goals"] . "-" . $matches[0]["home_goals"] . "\n\r '" .  $event["time"]["elapsed"] . "   " . "[Var]تم الغاء الهدف";
                }else{
                    $NotificationBodyEn = $matches[0]["home_goals"] . "-" . $matches[0]["away_goals"] . "\n\r '" .  $event["time"]["elapsed"] . "   " . " The Penalty is confirmed for " . " team";
                    $NotificationBodyAr = $matches[0]["away_goals"] . "-" . $matches[0]["home_goals"] . "\n\r '" .  $event["time"]["elapsed"] . "   " . " تم احتساب ضربة الجزاء لفريق ";
                }
                
                //echo $NotificationBodyEn . "   " . $NotificationBodyAr . "\n";

            } 
                                    
           
        }

    }
}
