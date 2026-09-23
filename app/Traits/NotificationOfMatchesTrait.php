<?php

namespace App\Traits;

use App\Models\Team;
use App\Models\Matche;
use App\Models\Player;
use App\Traits\Notify;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Http\Resources\MatchResource;
use App\Models\MatchEvent;
use Illuminate\Support\Str;


trait NotificationOfMatchesTrait{
    
    use Notify;

    public function matchStatus($value,$league,$match,$matches)
    {
        // foreach ($data as $value) {
            if (!in_array($value["league"]["id"], $league)) {
                // continue;
                return false;
            }
           

            $match->update([
                'status' => $value["fixture"]["status"]["short"],
                'elapsed' => $value["fixture"]["status"]["elapsed"],
                'match_date' => $value["fixture"]["date"]
            ]);
            $status = $value["fixture"]["status"]["short"];
            $notificationSentDatabase = $match->notificationSent;
            $notificationSentDatabaseArray = explode(',', $notificationSentDatabase);

            if ($status == "1H") {
                $this->sendMatchStatus($match,$matches,"H1",$notificationSentDatabaseArray,"بداية المباراة ","Match Started ",false,true);
            }elseif ($status == "HT"){
                $this->sendMatchStatus($match,$matches,"HT",$notificationSentDatabaseArray,"نهاية الشوط الأول","First Half Finished ",true,true);
            }elseif ($status == "2H") {
                $this->sendMatchStatus($match,$matches,"2H",$notificationSentDatabaseArray," بداية الشوط الثاني ","Second Half Started ",true,true);
            }elseif ($status == "FT") {
                $this->sendMatchStatus($match,$matches,"FT",$notificationSentDatabaseArray,"انتهت المباراة ","Match Finished ", true, true);
            }elseif ($status == "ET") {
                $this->sendMatchStatus($match,$matches,"ET",$notificationSentDatabaseArray," بداية الأشواط الاضافية ","Extra Time Started ",true, true);
            }elseif ($status == "BT") {
                $this->sendMatchStatus($match,$matches,"BT",$notificationSentDatabaseArray,"استراحة ما بين الشوطين الاضافيين ","Break During Extra Time ",true, true);
            }elseif ($status == "P") {
                $this->sendMatchStatus($match,$matches,"P",$notificationSentDatabaseArray," بداية ضربات الجزاء ","Penalty Started ",true, true);
            }elseif ($status == "AET") {
                $this->sendMatchStatus($match,$matches,"AET",$notificationSentDatabaseArray,"انتهى الشوطين الاضافيين و انتهت المباراة ","Match Finished After Extra Time ",true ,true);
            }elseif ($status == "PEN") {
                $this->sendMatchStatus($match,$matches,"PEN",$notificationSentDatabaseArray,"انتهت ضربات الجزاء و انتهت المباراة ","Match Finished After Penalty ",true, true);
            }

            return true;
        // }
    }

    public function events($data,$matches,$currentElapset,$matchId)
    {
        
        $type = strtolower($data["type"]);
        $detail = strtolower($data["detail"]);

        if ($type == 'subst') {
            return false;
        }

        // $extra = $data["time"]["extra"] == null ? 0 : $data["time"]["extra"];
        //     if (($currentElapset + $extra) > ($data["time"]["elapsed"] . ($data['time']['extra'] == null ? '' : ' + '.$data['time']['extra']) + $extra + 3)) {
        //         return false;
        // }

        $teamNotification = Team::where('team_id', $data["team"]["id"])->first();
        if (!$teamNotification) {
            $teamNotification = $this->createTeam($data);
            // return false;
        }

        $player = Player::where('player_id', $data["player"]["id"])->first(); // get player
        $playerImage = null;
        if ($player) {
            $playerId = $player->id;
            $playerNameEn = $player->translate('en')->first_name . " " . $player->translate('en')->last_name;
            $playerNameAr = $player->translate('ar')->first_name . " " . $player->translate('ar')->last_name;
            $playerImage = $player->image_path;
        }else{
            $playerNameAr = $data['player']['name'] ?? null;
            $playerNameEn = $data['player']['name'] ?? null;
            $playerImage = $teamNotification->image_path;
        }

        if ($playerNameEn == "Isaac Lihadji") {
            $playerNameEn = null;
            $playerNameAr = null;
            $playerImage = $teamNotification->image_path;
        }

        $teamEn = $teamNotification->translate('en')->name;
        $teamAr = $teamNotification->translate('ar')->name;

        if ($playerNameEn == null) {
            $playerNameEn = "From Team ". $teamNotification->translate('en')->name ." ";
            $playerNameAr = "من فريق ". $teamNotification->translate('ar')->name ." ";
        }

        if ($type == "var") {
            if ($detail == null || $detail == "") {
                $NotificationBodyEn = $matches[0]["home_goals"] . "-" . $matches[0]["away_goals"] . "\n\r '" .  $data["time"]["elapsed"] . ($data['time']['extra'] == null ? '' : ' + '.$data['time']['extra']) . "   " . "There are a var now in match [VAR]";
                $NotificationBodyAr = $matches[0]["away_goals"] . "-" . $matches[0]["home_goals"] . "\n\r '" .  $data["time"]["elapsed"] . ($data['time']['extra'] == null ? '' : ' + '.$data['time']['extra']) . "   " . " جاري مراجعة ال[VAR] حاليا";
                $this->sendNotificationsToUsers($matches,$NotificationBodyEn,$NotificationBodyAr,false);
                return true;
            }
        }

        $extra = $data['time']['extra'] == null ? 0 : $data['time']['extra'];

        $storedEvent = MatchEvent::where([
            'minute' => $data["time"]["elapsed"] + intval($extra) ,
            'status' => $type.'-'.$detail,
            'team_id' => $teamNotification->id,
            'match_id' => $matchId,
            'event_type' => 'AUTO'
        ])->count();

        if ($storedEvent > 0) {
            return false;
        }

         if($this->checkEventSent(intval($data["time"]["elapsed"]) + intval($extra) + 1, $type.'-'.$detail, $teamNotification->id, $matchId) == false){
            return false;
         }

         if($this->checkEventSent(intval($data["time"]["elapsed"]) + intval($extra) - 1, $type.'-'.$detail, $teamNotification->id, $matchId) == false){
            return false;
         }
        

        $this->storeEvent(intval($data["time"]["elapsed"]) + intval($extra) ,$type.'-'.$detail,$playerNameEn,$playerId ?? null, $teamNotification,$matchId);

       
        if ($type == "card") {
            if ($playerNameEn != null) {
                if ($detail == "yellow card") {
                    $NotificationBodyAr = $matches[0]["away_goals"] . "-" . $matches[0]["home_goals"] . "\n\r '" .  $data["time"]["elapsed"] . ($data['time']['extra'] == null ? '' : ' + '.$data['time']['extra']) . "   " . "حصل اللاعب " . $playerNameAr . " على كرت أصفر";
                    $NotificationBodyEn = $matches[0]["home_goals"] . "-" . $matches[0]["away_goals"] . "\n\r '" .  $data["time"]["elapsed"] . ($data['time']['extra'] == null ? '' : ' + '.$data['time']['extra']) . "   " . $playerNameEn . " took " . $detail;
                }elseif ($detail == "second yellow card") {
                    $NotificationBodyAr = $matches[0]["away_goals"] . "-" . $matches[0]["home_goals"] . "\n\r '" .  $data["time"]["elapsed"] . ($data['time']['extra'] == null ? '' : ' + '.$data['time']['extra']) . "   " . "حصل اللاعب " . $playerNameAr . " على 2 كرت أصفر";
                    $NotificationBodyEn = $matches[0]["home_goals"] . "-" . $matches[0]["away_goals"] . "\n\r '" .  $data["time"]["elapsed"] . ($data['time']['extra'] == null ? '' : ' + '.$data['time']['extra']) . "   " . $playerNameEn . " took " . $detail;
                }elseif ($detail == "red card") {
                    $NotificationBodyAr = $matches[0]["away_goals"] . "-" . $matches[0]["home_goals"] . "\n\r '" .  $data["time"]["elapsed"] . ($data['time']['extra'] == null ? '' : ' + '.$data['time']['extra']) . "   " . "حصل اللاعب " . $playerNameAr . " على كرت أحمر";
                    $NotificationBodyEn = $matches[0]["home_goals"] . "-" . $matches[0]["away_goals"] . "\n\r '" .  $data["time"]["elapsed"] . ($data['time']['extra'] == null ? '' : ' + '.$data['time']['extra']) . "   " . $playerNameEn . " took " . $detail;
                }
            }else{
                if ($detail == "yellow card") {
                    $NotificationBodyAr = $matches[0]["away_goals"] . "-" . $matches[0]["home_goals"] . "\n\r '" .  $data["time"]["elapsed"] . ($data['time']['extra'] == null ? '' : ' + '.$data['time']['extra']) . "   " . "كارت اصفر لصالح فريق ". $teamAr . " ";
                    $NotificationBodyEn = $matches[0]["home_goals"] . "-" . $matches[0]["away_goals"] . "\n\r '" .  $data["time"]["elapsed"] . ($data['time']['extra'] == null ? '' : ' + '.$data['time']['extra']) . "   " . $teamEn . " took " . $detail;
                }elseif ($detail == "second yellow card") {
                    $NotificationBodyAr = $matches[0]["away_goals"] . "-" . $matches[0]["home_goals"] . "\n\r '" .  $data["time"]["elapsed"] . ($data['time']['extra'] == null ? '' : ' + '.$data['time']['extra']) . "   " . "كارت اصفر لصالح فريق 2".' '. $teamAr . " ";
                    $NotificationBodyEn = $matches[0]["home_goals"] . "-" . $matches[0]["away_goals"] . "\n\r '" .  $data["time"]["elapsed"] . ($data['time']['extra'] == null ? '' : ' + '.$data['time']['extra']) . "   " . $teamEn . " took " . $detail;
                }elseif ($detail == "red card") {
                    $NotificationBodyAr = $matches[0]["away_goals"] . "-" . $matches[0]["home_goals"] . "\n\r '" .  $data["time"]["elapsed"] . ($data['time']['extra'] == null ? '' : ' + '.$data['time']['extra']) . "   " . "كارت احمر لصالح فريق ". $teamAr . " ";
                    $NotificationBodyEn = $matches[0]["home_goals"] . "-" . $matches[0]["away_goals"] . "\n\r '" .  $data["time"]["elapsed"] . ($data['time']['extra'] == null ? '' : ' + '.$data['time']['extra']) . "   " . $teamEn . " took " . $detail;
                }
            }
        }

        if ($type == "goal") {
           if ($playerNameEn != null) {
                if ($detail == 'penalty') {
                    $NotificationBodyEn = $matches[0]["home_goals"] . "-" . $matches[0]["away_goals"] . "\n\r '" .  $data["time"]["elapsed"] . ($data['time']['extra'] == null ? '' : ' + '.$data['time']['extra']) . "   " . $playerNameEn . " scored goal with penalty for " . $teamNotification->translate('en')->name . " team";
                    $NotificationBodyAr = $matches[0]["away_goals"] . "-" . $matches[0]["home_goals"] . "\n\r '" .  $data["time"]["elapsed"] . ($data['time']['extra'] == null ? '' : ' + '.$data['time']['extra']) . "   " . "سجل اللاعب " . $playerNameAr . " هدفا بضربة جزاء لصالح فريق " . $teamNotification->translate('ar')->name;

                }elseif ($detail == "missed penalty") {
                    $NotificationBodyEn = $matches[0]["home_goals"] . "-" . $matches[0]["away_goals"] . "\n\r '" .  $data["time"]["elapsed"] . ($data['time']['extra'] == null ? '' : ' + '.$data['time']['extra']) . "   " . " Missed penalty for " . $teamNotification->translate('en')->name . " team";
                    $NotificationBodyAr = $matches[0]["away_goals"] . "-" . $matches[0]["home_goals"] . "\n\r '" .  $data["time"]["elapsed"] . ($data['time']['extra'] == null ? '' : ' + '.$data['time']['extra']) . "   " . " ضربة جزاء ضائعه " . $teamNotification->translate('ar')->name;
                }elseif ($detail == "own goal") {
                    $NotificationBodyEn = $matches[0]["home_goals"] . "-" . $matches[0]["away_goals"] . "\n\r '" .  $data["time"]["elapsed"] . ($data['time']['extra'] == null ? '' : ' + '.$data['time']['extra']) . "   " . $playerNameEn . " scored an own goal for " . $teamNotification->translate('en')->name . " team";
                    $NotificationBodyAr = $matches[0]["away_goals"] . "-" . $matches[0]["home_goals"] . "\n\r '" .  $data["time"]["elapsed"] . ($data['time']['extra'] == null ? '' : ' + '.$data['time']['extra']) . "   " . "سجل اللاعب " . $playerNameAr . " هدفا في مرماه لصالح فريق " . $teamNotification->translate('ar')->name;
                }else{
                    $NotificationBodyEn = $matches[0]["home_goals"] . "-" . $matches[0]["away_goals"] . "\n\r '" .  $data["time"]["elapsed"] . ($data['time']['extra'] == null ? '' : ' + '.$data['time']['extra']) . "   " . $playerNameEn . " scored goal for " . $teamNotification->translate('en')->name . " team";
                    $NotificationBodyAr = $matches[0]["away_goals"] . "-" . $matches[0]["home_goals"] . "\n\r '" .  $data["time"]["elapsed"] . ($data['time']['extra'] == null ? '' : ' + '.$data['time']['extra']) . "   " . "سجل اللاعب " . $playerNameAr . " هدفا لصالح فريق " . $teamNotification->translate('ar')->name;
                }
           }else {
                if ($detail == 'penalty') {
                    $NotificationBodyEn = $matches[0]["home_goals"] . "-" . $matches[0]["away_goals"] . "\n\r '" .  $data["time"]["elapsed"] . ($data['time']['extra'] == null ? '' : ' + '.$data['time']['extra']) . "   " . " goal with penalty for ".' ' . $teamNotification->translate('en')->name . " team";
                    $NotificationBodyAr = $matches[0]["away_goals"] . "-" . $matches[0]["home_goals"] . "\n\r '" .  $data["time"]["elapsed"] . ($data['time']['extra'] == null ? '' : ' + '.$data['time']['extra']) . "   " . " هدف بضربة جزاء لصالح فريق ".' '. $teamNotification->translate('ar')->name;

                }elseif ($detail == "missed penalty") {
                    $NotificationBodyEn = $matches[0]["home_goals"] . "-" . $matches[0]["away_goals"] . "\n\r '" .  $data["time"]["elapsed"] . ($data['time']['extra'] == null ? '' : ' + '.$data['time']['extra']) . "   " . " Missed penalty for " . $teamNotification->translate('en')->name . " team";
                    $NotificationBodyAr = $matches[0]["away_goals"] . "-" . $matches[0]["home_goals"] . "\n\r '" .  $data["time"]["elapsed"] . ($data['time']['extra'] == null ? '' : ' + '.$data['time']['extra']) . "   " . " ضربة جزاء ضائعه لفريق".' ' . $teamNotification->translate('ar')->name;
                }elseif ($detail == "own goal") {
                    $NotificationBodyEn = $matches[0]["home_goals"] . "-" . $matches[0]["away_goals"] . "\n\r '" .  $data["time"]["elapsed"] . ($data['time']['extra'] == null ? '' : ' + '.$data['time']['extra']) . "   " . "an own goal for " . $teamNotification->translate('en')->name . " team";
                    $NotificationBodyAr = $matches[0]["away_goals"] . "-" . $matches[0]["home_goals"] . "\n\r '" .  $data["time"]["elapsed"] . ($data['time']['extra'] == null ? '' : ' + '.$data['time']['extra']) . "   " . " هدف ذاتي في مرماه لصالح فريق " . $teamNotification->translate('ar')->name;
                }else{
                    $NotificationBodyEn = $matches[0]["home_goals"] . "-" . $matches[0]["away_goals"] . "\n\r '" .  $data["time"]["elapsed"] . ($data['time']['extra'] == null ? '' : ' + '.$data['time']['extra']) . "   " . " scored a goal for " . $teamNotification->translate('en')->name . " team";
                    $NotificationBodyAr = $matches[0]["away_goals"] . "-" . $matches[0]["home_goals"] . "\n\r '" .  $data["time"]["elapsed"] . ($data['time']['extra'] == null ? '' : ' + '.$data['time']['extra']) . "   " . " هدف لصالح فريق " .' '. $teamNotification->translate('ar')->name;
                }
           }
        }

        if ($type == "var" ) {
            if($detail == "goal cancelled"){
                $NotificationBodyEn = $matches[0]["home_goals"] . "-" . $matches[0]["away_goals"] . "\n\r '" .  $data["time"]["elapsed"] . ($data['time']['extra'] == null ? '' : ' + '.$data['time']['extra']) . "   " . "Goal Cancelled [VAR]";
                $NotificationBodyAr = $matches[0]["away_goals"] . "-" . $matches[0]["home_goals"] . "\n\r '" .  $data["time"]["elapsed"] . ($data['time']['extra'] == null ? '' : ' + '.$data['time']['extra']) . "   " . "[VAR] تم الغاء الهدف";
            }elseif ($detail == "penalty confirmed"){
                $NotificationBodyEn = $matches[0]["home_goals"] . "-" . $matches[0]["away_goals"] . "\n\r '" .  $data["time"]["elapsed"] . ($data['time']['extra'] == null ? '' : ' + '.$data['time']['extra']) . "   " . "[VAR] The Penalty is confirmed for " . $teamNotification->translate('en')->name . " team";
                $NotificationBodyAr = $matches[0]["away_goals"] . "-" . $matches[0]["home_goals"] . "\n\r '" .  $data["time"]["elapsed"] . ($data['time']['extra'] == null ? '' : ' + '.$data['time']['extra']) . "   " . "[VAR] تم احتساب ضربة الجزاء لفريق " . $teamNotification->translate('ar')->name;
            }elseif($detail == "goal disallowed - offside"){
                $NotificationBodyEn = $matches[0]["home_goals"] . "-" . $matches[0]["away_goals"] . "\n\r '" .  $data["time"]["elapsed"] . ($data['time']['extra'] == null ? '' : ' + '.$data['time']['extra']) . "   " . "Goal Disallowed - offside [VAR]";
                $NotificationBodyAr = $matches[0]["away_goals"] . "-" . $matches[0]["home_goals"] . "\n\r '" .  $data["time"]["elapsed"] . ($data['time']['extra'] == null ? '' : ' + '.$data['time']['extra']) . "   " . "[VAR] الهدف غير مسموح به - تسلل ";
            }elseif($detail == "goal disallowed"){
                $NotificationBodyEn = $matches[0]["home_goals"] . "-" . $matches[0]["away_goals"] . "\n\r '" .  $data["time"]["elapsed"] . ($data['time']['extra'] == null ? '' : ' + '.$data['time']['extra']) . "   " . "Goal Cancelled [VAR]";
                $NotificationBodyAr = $matches[0]["away_goals"] . "-" . $matches[0]["home_goals"] . "\n\r '" .  $data["time"]["elapsed"] . ($data['time']['extra'] == null ? '' : ' + '.$data['time']['extra']) . "   " . "[VAR] تم الغاء الهدف";
            }elseif($detail == "goal allowed"){
                $NotificationBodyEn = $matches[0]["home_goals"] . "-" . $matches[0]["away_goals"] . "\n\r '" .  $data["time"]["elapsed"] . ($data['time']['extra'] == null ? '' : ' + '.$data['time']['extra']) . "   " . "Goal Allowed [VAR]";
                $NotificationBodyAr = $matches[0]["away_goals"] . "-" . $matches[0]["home_goals"] . "\n\r '" .  $data["time"]["elapsed"] . ($data['time']['extra'] == null ? '' : ' + '.$data['time']['extra']) . "   " . "[VAR] تم احتساب الهدف";
            }
            
            else{
                $NotificationBodyEn = $matches[0]["home_goals"] . "-" . $matches[0]["away_goals"] . "\n\r '" .  $data["time"]["elapsed"] . ($data['time']['extra'] == null ? '' : ' + '.$data['time']['extra']) . "   " . "There are a var now in match [VAR]";
                $NotificationBodyAr = $matches[0]["away_goals"] . "-" . $matches[0]["home_goals"] . "\n\r '" .  $data["time"]["elapsed"] . ($data['time']['extra'] == null ? '' : ' + '.$data['time']['extra']) . "   " . " جاري مراجعة ال[VAR] حاليا";
            }
        }

        $this->sendNotificationsToUsers($matches,$NotificationBodyEn,$NotificationBodyAr,false,false,$playerImage);

        // }
        return true;

    }

    public function createTeam($data)
    {
        $team = Team::create([
            'en' => [
                'name' => $data["teams"]["name"]
            ],
            'ar' => [
                'name' => $data["teams"]["name"]
            ],
            'team_id' => $data["teams"]["id"],
            'image' => $data["teams"]["logo"],
        ]);

        return $team;
    }

    

    public function sendMatchStatus($match,$matches,$time,$notificationSentDatabaseArray,$message_ar,$message_en,$showGoals = false,$mergeImages = false) 
    {
        
        if (!in_array($time, $notificationSentDatabaseArray)) {
            $match->notificationSent = $time == "H1" ? $time : ','.$time;
            $match->save();
            $this->sendNotificationsToUsers($matches,$message_en,$message_ar,$showGoals,$mergeImages);
        }
        return true;
    }

    public function matchArrayFromFixtureId($fixtureId,$home_goals,$away_goals)
    {
        return Matche::query()->where('fixture_id', $fixtureId)->with('competition.parent')->get()->each(function ($builder) use($home_goals,$away_goals) {
            $builder->home_team_name_ar = $builder->team1()->first()->translate('ar')->name;
            $builder->home_team_name_en = $builder->team1()->first()->translate('en')->name;
            $builder->away_team_name_ar = $builder->team2()->first()->translate('ar')->name;
            $builder->away_team_name_en = $builder->team2()->first()->translate('en')->name;
            $builder->home_goals = $home_goals;
            $builder->away_goals = $away_goals;
            $builder->home_image = $builder->team1()->first()->image_path;
            $builder->away_image = $builder->team2()->first()->image_path;
            // $builder->home_goals = $builder->events()->getQuery()->where('status', 'goal')->where('team_id', $builder->team1_id)->get()->count();
            // $builder->away_goals = $builder->events()->getQuery()->where('status', 'goal')->where('team_id', $builder->team2_id)->get()->count();
        });
    }

    public function sendNotificationsToUsers($matches,$message_en,$message_ar,$showGoals,$mergeImages = false, $playerImage = null)
    {
            $teams = collect();
            $matches->pluck('team1_id')->each(function ($item, $key) use ($teams) {
                $teams->push($item);
            });
            $matches->pluck('team2_id')->each(function ($item, $key) use ($teams) {
                $teams->push($item);
            });

            $ClientsGotEventNotification = array();
            foreach ($teams as $team) {
                $ClientsGotEventNotification = $this->notifyUsers($team, $matches, $ClientsGotEventNotification,$message_en,$message_ar,$showGoals,$mergeImages, $playerImage);
            }
            return true;
    }

    public function notifyUsers($team, $matches,$ClientsGotEventNotification, $message_en, $message_ar, $showGoals = false, $mergeImages = false, $playerImage = null)
    {
        $client_ids = DB::table('favourite_team')->where('team_id', $team)->get()->pluck('client_id')->toArray();
        $clients_did_not_got_notification = array_diff($client_ids, $ClientsGotEventNotification);
        $ClientsGotEventNotification = array_merge($ClientsGotEventNotification, $clients_did_not_got_notification);
        $client_tokens_en = DB::table('clients')->where('locale', 'en')->whereIn('id', $clients_did_not_got_notification)->get()->pluck('fb_token');
        $client_tokens_ar = DB::table('clients')->where('locale', 'ar')->whereIn('id', $clients_did_not_got_notification)->get()->pluck('fb_token');
        $getTeam = Team::query()->find($team);
        $this->topicNotifyByFirebaseTokens($client_tokens_en, [
            'title' => $matches[0]["home_team_name_en"] . "-" . $matches[0]["away_team_name_en"],
            'body' => $showGoals == true ? $matches[0]["home_goals"] . "-" . $matches[0]["away_goals"] . "\r\n" . "  " . $message_en : $message_en,
            // 'image' => $getTeam->image_path,
            'image' => $playerImage != null ? $playerImage : ($mergeImages == false ? $getTeam->image_path : $this->mergeImagesInOneImage($matches[0]["away_image"],$matches[0]["home_image"])),
            'notify' => [
                'fixture_id' => (string) $matches[0]->fixture_id,
                'match_id' => (string) $matches[0]->id,
                'team_id'  => (string) $team,
            ]
        ]);
        $this->topicNotifyByFirebaseTokens($client_tokens_ar, [
            'title' => $matches[0]["home_team_name_ar"] . "-" . $matches[0]["away_team_name_ar"],
            'body' => $showGoals == true ? $matches[0]["away_goals"] . "-" . $matches[0]["home_goals"] . "\r\n" ."  " . $message_ar : $message_ar,
            // 'image' => $getTeam->image_path,
            'image' => $playerImage != null ? $playerImage : ($mergeImages == false ? $getTeam->image_path : $this->mergeImagesInOneImage($matches[0]["away_image"],$matches[0]["home_image"])),
            'notify' => [
                'fixture_id' => (string) $matches[0]->fixture_id,
                'match_id' => (string) $matches[0]->id,
                'team_id'  => (string) $team,
            ]
        ]);

        return $ClientsGotEventNotification;
    }

 
    public function storeEvent($minute,$type,$playerNameEn,$playerId,$teamNotification,$matchId)
    {
        $matchEvent = MatchEvent::create([
            'minute' => $minute,
            'status' => $type,
            'player_name' => $playerNameEn,
            'player_id' => $playerId,
            'team_id' => $teamNotification->id,
            'match_id' => $matchId,
            'event_type' => 'AUTO'
        ]);

        return $matchEvent;
    }

    public function checkEventSent($minutes,$type,$teamNotification,$matchId)
    {
        $storedEvent = MatchEvent::where([
            'minute' => $minutes,
            'status' => $type,
            'team_id' => $teamNotification,
            'match_id' => $matchId,
            'event_type' => 'AUTO'
        ])->count();

        if ($storedEvent > 0) {
            return false;
        }else{
            return true;
        }
    }

    public function mergeImagesInOneImage($img_left_local, $img_right_local)
    {
        if ($img_left_local == null) {
            return $img_right_local;
        }

        if ($img_right_local == null) {
            return $img_left_local;
        }

        try {
            $imageManager = new \Intervention\Image\ImageManager;
            $img_canvas = $imageManager->canvas(120,115);
            $img_canvas->insert($imageManager->make($img_right_local)->resize(79,73), 'top-left', 30, 0);
            $img_canvas->insert($imageManager->make($img_left_local)->resize(79,73), 'top-left', 5, 40);
            $nameHash = Str::random(10);
            $img_canvas->save(public_path()."/storage/vs_images/". $nameHash .".png", 100);
            return url('/')."/storage/vs_images/". $nameHash .".png";
        }catch (\Throwable $th) {
            return null;
        }
    }

    // public function LastEventAdded($matchId) {
    //     $matchEventsDatabase = MatchEvent::where('match_id', $matchId)->orderByDesc('id')->get();
    //     $lastEventAdded = $matchEventsDatabase->first();

    //     foreach ($matchEventsDatabase as $events) {
    //         $events->delete();
    //     }
    //     return $lastEventAdded;

    // In event function : 
     // if ($lastEventAdded->minute != $data["time"]["elapsed"] . ($data['time']['extra'] == null ? '' : ' + '.$data['time']['extra']) && $lastEventAdded->status != $type && $lastEventAdded->match_id == $matchId) {
            // if (collect($event)->last() == $data && $lastEventAdded->minute != $data["time"]["elapsed"] . ($data['time']['extra'] == null ? '' : ' + '.$data['time']['extra']) ) {
     // }
    // }

    public function sendNotifications($matches, $message_en, $message_ar)
    {
        foreach($matches as $key2=>$match){
            $teams = collect();
            $teams->push($matches[$key2]->team1_id);
            $teams->push($matches[$key2]->team2_id);

            $ClientsGotEventNotification = array();
            foreach ($teams as $team) {
                $client_ids = DB::table('favourite_team')->where('team_id', $team)->get()->pluck('client_id')->toArray();
                $clients_did_not_got_notification = array_diff($client_ids, $ClientsGotEventNotification);
                $ClientsGotEventNotification = array_merge($ClientsGotEventNotification, $clients_did_not_got_notification);
                $client_tokens_en = DB::table('clients')->where('locale', 'en')->whereIn('id', $clients_did_not_got_notification)->get()->pluck('fb_token');
                $client_tokens_ar = DB::table('clients')->where('locale', 'ar')->whereIn('id', $clients_did_not_got_notification)->get()->pluck('fb_token');
                // $getTeam = Team::query()->find($team);
                $this->topicNotifyByFirebaseTokens($client_tokens_en, [
                    'title'=> $matches[$key2]["home_team_name_en"] . "-" . $matches[$key2]["away_team_name_en"],
                    'body'=> $message_en,
                    // 'image'=>$getTeam->image_path,
                    'image' => $this->mergeImagesInOneImage($matches[$key2]["away_image"],$matches[$key2]["home_image"]),
                    'notify'=>(object) array('match' => new MatchResource($matches[$key2]))
                ]);
                $this->topicNotifyByFirebaseTokens($client_tokens_ar, [
                    'title'=> $matches[$key2]["home_team_name_ar"] . "-" . $matches[$key2]["away_team_name_ar"],
                    'body'=> $message_ar,
                    // 'image'=>$getTeam->image_path,
                    'image' => $this->mergeImagesInOneImage($matches[$key2]["away_image"],$matches[$key2]["home_image"]),
                    'notify'=>(object) array('match' => new MatchResource($matches[$key2]))
                ]);
            }
        }
    }

    public function translateKeyLeague($key)
    {
        $array = [
            "Final Stages Final" => "المرحلة النهائية - النهائي",
            "Group B Round" => "المجموعة ب - الجولة",
            "Group 1" => "المجموعة الأولى",
            "Group 2" => "المجموعة الثانية",
            "Group 3" => "المجموعة الثالثة",
            "Group 4" => "المجموعة الرابعة",
            "Group 5" => "المجموعة الخامسة",
            "Group 6" => "المجموعة السادسة",
            "Group 7" => "المجموعة السابعة",
            "Group 8" => "المجموعة الثامنة",
            "Group 9" => "المجموعة التاسعة",
            "Group 10" => "المجموعة العاشرة",
            "Group 11" => "المجموعة الحادية عشر",
            "Group 12" => "المجموعة الثانية عشر",
            "week" => "الاسبوع",
            "group" => "المجموعة",
            "Semi-finals" => "نصف النهائي",
            "Semi-final" => "نصف النهائي",
            "Quarter-finals" => "الدور ربع النهائي",
            "Final" => "النهائي",
            "Finals" => "النهائي",
            "Round of" => "دور ال",
            "knockout round play-offs" => "جولة خروج المغلوب",
            "Knockout Round Play-offs" => "جولة خروج المغلوب",
            "Play-offs" => "تصفيات",
            "Play-off" => "تصفيات",
            "Goal Disallowed - foul" => "ألغي الهدف - خطأ",
            "Goal Disallowed - handball" => "ألغي الهدف - لمسة يد",
            "Regular Season" => "الاسبوع",
            "Preliminary Round Replays" => "إعادة الدور التمهيدي",
            "Extra Preliminary Round" => "الدور التمهيدي الإضافي",
            "Extra Preliminary Round Replays" => "إعادة الدور التمهيدي الإضافي",
            "1st preliminary round" => "الدور التمهيدي الأول",
            "4th Round Qualifying" => "الجولة التأهيلية الرابعة",
            "5th Round Qualifying" => "الجولة التأهيلية الخامسة",
            "6th Round Qualifying" => "الجولة التأهيلية السادسة",
            "1st Round Qualifying Replays" => "اعادة الجولة التأهيلية الأولي",
            "3rd Round Qualifying Replays" => "اعادة الجولة التأهيلية الثالثة",
            "Preliminary Round" => "الدور التمهيدي",
            "1st Preliminary Round" => "الدور التمهيدي الأول",
            "2nd preliminary round" => "الدور التمهيدي الثاني",
            "2nd Preliminary Round" => "الدور التمهيدي الثاني",
            "Group stage" => "دور المجموعات",
            "Group Stage" => "دور المجموعات",
            "Group A" => "المجموعة الأولى",
            "Group B" => "المجموعة الثانية",
            "Group C" => "المجموعة الثالثة",
            "Group D" => "المجموعة الرابعة",
            "Group E" => "المجموعة الخامسة",
            "Group F" => "المجموعة السادسة",
            "Group G" => "المجموعة السابعة",
            "Group H" => "المجموعة الثامنة",
            "Group I" => "المجموعة التاسعة",
            "Group J" => "المجموعة العاشرة",
            "Group K" => "المجموعة الحادية عشر",
            "League A" => "المجموعة الأولى",
            "League B" => "المجموعة الثانية",
            "League C" => "المجموعة الثالثة",
            "League D" => "المجموعة الرابعة",
            "League E" => "المجموعة الخامسة",
            "League F" => "المجموعة السادسة",
            "League G" => "المجموعة السابعة",
            "League H" => "المجموعة الثامنة",
            "1st Round" => "الجولة الأولى",
            "2nd Round" => "الجولة الثانية",
            "2nd Qualifying Round" => "الجولة التأهيلية الثانية",
            "2nd Round Qualifying Replays" => "إعادة الجولة التأهيلية الثانية",
            "2rd Qualifying Round" => "الجولة التأهيلية الثانية",
            "2nd Round Qualifying" => "الجولة التأهيلية الثانية",
            "3rd Round Qualifying" => "الجولة التأهيلية الثالثة",
            "3rd Qualifying Round" => "الجولة التأهيلية الثالثة",
            "1st Qualifying Round" => "الجولة التأهيلية الأولى",
            "1st Round Qualifying" => "الجولة التأهيلية الأولى",
            "8th Finals" => "ربع النهائي",
            "16th Finals" => "دور ال 16",
            "Round of 32" => "دور ال 32",
            "3rd Place Final" => "مباراة تحديد المركز الثالث",
            "4th Round Qualifying Replays" => "إعادة الجولة التأهيلية الثانية",
            "3rd Round" => "الجولة الثالثة",
            "4th Round" => "الجولة الرابعة",
            "5th Round" => "الجولة الخامسة",
            "6th Round" => "الجولة السادسة",
            "7th Round" => "الجولة السابعة",
            "8th Round" => "الجولة الثامنة",
            "9th Round" => "الجولة التاسعة",
            "1st Round Replays" => "إعادة الجولة الأولى",
            "2nd Round Replays" => "إعادة الجولة الثانية",
            "3rd Round Replays" => "إعادة الجولة الثالثة",
            "4th Round Replays" => "إعادة الجولة الرابعة",
            "5th Round Replays" => "إعادة الجولة الخامسة",
            "2nd Qualifying Round" => "الجولة التأهيلية الثانية",
            "2rd Qualifying Round" => "الجولة التأهيلية الثانية",
            "3nd Qualifying Round" => "الجولة التأهيلية الثالثة",
            "Qualifying Round" => "الجولة التأهيلية",
            "Relegation Round" => "جولة الهبوط",
            "Championship Round" => "جولة البطولة",
            "Qualifying Play-offs Path A- Final" => "مسار التصفيات المؤهلة أ - النهائي",
            "Qualifying Play-offs Path D - Final" => "مسار التصفيات المؤهلة د - النهائي",
            "Qualifying Play-offs Path B - Final" => "مسار التصفيات المؤهلة ب - النهائي",
            "Qualifying Play-offs Path C - Final" => "مسار التصفيات المؤهلة ج - النهائي",
            "Round of 64" => "دور ال 64",
            "Fase a gironi" => "مرحلة المجموعات",
            "MLS Cup - Round 1" => "تصفيات الدور الأول",
            "MLS Cup Conference Semi-finals" => "قبل نهائي المؤتمر",
            "MLS Cup Conference Finals" => "نهائي المؤتمر",
            "Championship Semi-finals" => "قبل نهائي البطولة",
            "Championship Final" => "نهائي البطولة",
            "1st Phase - Final" => "نهائي المرحلة الأولى",
            "1st Phase - Quarter finals" => "ربع نهائي المرحلة الأولى",
            "2nd Phase - Semi-finals" => "نصف نهائي المرحلة الثانية",
            "2nd Phase - Final" => "نهائي المرحلة الثانية",
            "2nd Phase - Quarter finals" => "ربع نهائي المرحلة الثانية",
            "2nd Phase - Semi-finals" => "نصف نهائي المرحلة الثانية",
            "1st Phase - Quarter-finals" => "ربع نهائي المرحلة الأولى",
            "Friendlies" => "وديات",
            "Club Friendlies" => "وديات أندية",
            "1st Phase" => "المرحلة الأولى",
            "2nd Phase" => "المرحلة الثانية",
        ];

        return $array[$key];
    }
}