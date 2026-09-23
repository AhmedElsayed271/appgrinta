<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CompetitionResource;
use App\Http\Resources\MatchResource;
use App\Http\Resources\PostPaginationResource;
use App\Http\Resources\TeamResource;
use App\Models\Competition;
use App\Models\Matche;
use App\Models\Setting;
use App\Models\Team;
use App\Traits\ApiResponser;
use App\Traits\TablesQuery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TeamsController extends Controller
{
    use ApiResponser,TablesQuery;
    public function index() : \Illuminate\Http\JsonResponse
    {
        return $this->showAll(TeamResource::collection(Team::all())->collection);
        // return $this->successResponse(TeamResource::collection(Team::all())->collection, 200);
    }
    public function show($id): \Illuminate\Http\JsonResponse
    {
        $team=Team::query()->where('team_id',$id)->first();
        if($team == null ){
            $team=Team::query()->findOrFail($id);
        }
        return $this->successResponse(new TeamResource($team),200);
    }

    public function getTeam(Request $request)
    {
        $request->validate([
            'teams' => 'required'
        ]);
        $teamsIds = explode(',', $request->teams);
        // return $teamsIds;
        return $this->successResponse(TeamResource::collection(Team::whereIn('team_id', $teamsIds)->get())->collection, 200);
    }

    public function searchTeams(Request $request)
    {
        // return $request;
        // if ($request->locale == 'ar') {
        //     return $this->successResponse(TeamResource::collection(Team::query()->when($request->name, function ($query) use ($request) {
        //         $query->whereTranslationLike('name', 'LIKE', '%'.$request->name.'%');
        //     })->get())->collection, 200);
        // }else {
        //     return $this->successResponse(TeamResource::collection(Team::query()->when($request->name, function ($query) use ($request) {
        //         $query->where('name', 'LIKE', '%'.$request->name.'%');
        //     })->get())->collection, 200);
        // }

        return $this->successResponse(TeamResource::collection($this->search(Team::query(), Team::SEARCHFIELDS, $request->name)->get())->collection, 200);
    }

    public function teamd($id): \Illuminate\Http\JsonResponse
    {
        $team=Team::query()->where('id',$id)->first();
        if($team == null ){
            $team=Team::query()->findOrFail($id);
        }
        return $this->successResponse(new TeamResource($team),200);
    }
    public function posts($id): \Illuminate\Http\JsonResponse
    {
        $team=Team::query()->where('id',$id)->first();
        if($team == null ){
            $team=Team::query()->findOrFail($id);
        }
        $posts=$team->posts()->with('category')->get();
        return $this->successResponse(PostPaginationResource::make($this->showAllPagination($posts)),200);
    }
    public function competitions($id): \Illuminate\Http\JsonResponse
    {
        $team=Team::query()->where('team_id',$id)->first();
        if($team == null ){
            $team=Team::query()->findOrFail($id);
        }
        return $this->showAll(CompetitionResource::collection($team->competitions()->get())->collection);
    }

    public function matches($id): \Illuminate\Http\JsonResponse
    {
        $team=Team::query()->where('id',$id)->first();
        if($team == null ){
            $team=Team::query()->findOrFail($id);
        }
        $match_date=\request()->input('match_date');
        // $status = request()->input('status');

        $order = 'desc';

        if (request()->has('order')) {
            $order = request()->input('order');
        }


        $data = Matche::query()->when($match_date,function ($builder) use($match_date){
            return $builder->whereDate('match_date',$match_date);
        })->where('team1_id',$team->id)->orWhere('team2_id',$team->id)->with('competition');

        if ($order == 'asc') {
            $result = $data->orderBy('match_date', 'ASC')->get()->each(function ($builder){
                $builder->home_goals=$builder->events()->getQuery()->where('status','goal')->where('team_id',$builder->team1_id)->get()->count();
                $builder->away_goals=$builder->events()->getQuery()->where('status','goal')->where('team_id',$builder->team2_id)->get()->count();
            });
        }else{
            $result = $data->orderBy('match_date', 'DESC')->get()->each(function ($builder){
                $builder->home_goals=$builder->events()->getQuery()->where('status','goal')->where('team_id',$builder->team1_id)->get()->count();
                $builder->away_goals=$builder->events()->getQuery()->where('status','goal')->where('team_id',$builder->team2_id)->get()->count();
            });
        }

        return $this->showAll(MatchResource::collection($result)->collection);
    }

    public function standing($team_id,$competition_id): \Illuminate\Http\JsonResponse
    {
        $season = request()->input('season', null);

        if ($season == null) {
            $settings = Setting::find(1);
            $season = $settings->season;
        }
        // 2021-2022
        $season = intval(str_replace(' ','',$season));
        $completeSeason = $season.'-'. ($season + 1);


        $team=Team::query()->where('team_id',$team_id)->with('competitions')->first();
        if($team == null ){
            $team=Team::query()->with('competitions')->findOrFail($team_id);
        }
        $competitions=$team->competitions->whereIn('season', [$season, $completeSeason])->pluck('id')->toArray();
        $competition=Competition::query()->where('league_id',$competition_id)->whereIn('season', [$season, $completeSeason])->first();
        if($competition == null ){
            $competition=Competition::query()->findOrFail($competition_id);
        }
        $collection=collect();
        if (in_array($competition->id,$competitions)){
            $points = $this->getQuery(
                'points',
                [
                    'SUM(points.win) AS sum_win',
                    'SUM(points.draw) AS sum_draw',
                    'SUM(points.loss) AS sum_loss',
                    'SUM(points.goals_for) AS sum_goals_for',
                    'SUM(points.goals_against) AS sum_goals_against',
                    'points.team_id',
                ],
                [
                    ['left','teams','teams.id','=','points.team_id'],
                ],'points.competition_id,points.team_id')->get();

            $teams=$competition->teams()->get();
            foreach ($teams as $key=>$value){
                $team_point=$points->where('team_id',$value->id)->first();
                $collection->add([
                    'number'=>($key+1),
                    'competition'=>$competition,
                    'team_id'=>$team_point?$team_point->team_id:$value->id,
                    'name'=>$value->name,
                    'logo'=>$value->image_path,
                    'sum_win'=>$team_point?(int)$team_point->sum_win:0,
                    'sum_draw'=>$team_point?(int)$team_point->sum_draw:0,
                    'sum_loss'=>$team_point?(int)$team_point->sum_loss:0,
                    'sum_goals_for'=>$team_point?(int)$team_point->sum_goals_for:0,
                    'sum_goals_against'=>$team_point?(int)$team_point->sum_goals_against:0,
                    'differance_goals'=>$team_point?(int)($team_point->sum_goals_for-$team_point->sum_goals_against):0,
                    'points'=>$team_point?($team_point->sum_win*3)+($team_point->sum_draw):0,
                ]);
            }
            $collection=$collection->sortByDesc('sum_goals_for');
            $collectionArray=$collection->sortByDesc('points');
            $resultCollection=collect();
            foreach ($collectionArray->values() as $key=>$value){
                $value['number']=$key+1;
                $resultCollection->add($value);
            }
            return $this->successResponse($resultCollection->where('team_id',$team->id)->first(),200);
        }
        return $this->errorResponse('error',400);
    }
    public function standingCompetition($team_id): \Illuminate\Http\JsonResponse
    {


        $team=Team::query()->where('id',$team_id)->with('competitions')->first();
        if($team == null ){
            $team=Team::query()->with('competitions')->findOrFail($team_id);
        }
        // $competitions=$team->competitions->whereIn('season', [$season, $completeSeason]);
        $competitionTeam = DB::table('competition_team')->where('team_id', $team->id)->pluck('competition_id');
        $competitions = Competition::whereIn('id', $competitionTeam)->get();
        if (request()->has('season')) {
            $season = request()->input('season', null);
            if ($season == null) {
                $settings = Setting::find(1);
                $season = $settings->season;
            }
            // 2021-2022
            $season = intval(str_replace(' ','',$season));
            $completeSeason = $season.'-'. ($season + 1);
            $competitions = Competition::whereIn('id', $competitionTeam)->whereIn('season', [$season, $completeSeason])->get();
        }
        $result=[];
        foreach ($competitions as $competition){
            $points = $this->getQuery(
                'points',
                [
                    'SUM(points.win) AS sum_win',
                    'SUM(points.draw) AS sum_draw',
                    'SUM(points.loss) AS sum_loss',
                    'SUM(points.goals_for) AS sum_goals_for',
                    'SUM(points.goals_against) AS sum_goals_against',
                    'points.team_id',
                    'points.competition_id',
                ],
                [
                    ['left','teams','teams.id','=','points.team_id'],
                ],'points.competition_id,points.team_id')->get();
            // $getCompetition=Competition::query()->with(['teams','parent'])->find($competition->id);
            // $teams=$getCompetition->teams;
            $collection=collect();
            // foreach ($teams as $key=>$value){
                $team_point=$points->where('team_id',$team->id)->where('competition_id', $competition->id)->first();
                $collection->add([
                    // 'number'=>($key+1),
                    'competition'=>new CompetitionResource($competition),
                    'competition_id'=>$team_point?$team_point->competition_id: null,
                    'team_id'=>$team_point?$team_point->team_id:$team->id,
                    'name'=>$team->name,
                    'logo'=>$team->image_path,
                    'sum_win'=>$team_point?(int)$team_point->sum_win:0,
                    'sum_draw'=>$team_point?(int)$team_point->sum_draw:0,
                    'sum_loss'=>$team_point?(int)$team_point->sum_loss:0,
                    'sum_goals_for'=>$team_point?(int)$team_point->sum_goals_for:0,
                    'sum_goals_against'=>$team_point?(int)$team_point->sum_goals_against:0,
                    'differance_goals'=>$team_point?(int)($team_point->sum_goals_for-$team_point->sum_goals_against):0,
                    'points'=>$team_point?($team_point->sum_win*3)+($team_point->sum_draw):0,
                ]);
            // }
            $collection=$collection->sortByDesc('sum_goals_for');
            $collectionArray=$collection->sortByDesc('points');
            $resultCollection=collect();
            foreach ($collectionArray->values() as $key=>$value){
                $value['number']=$key+1;
                $resultCollection->add($value);
            }
            $result[]=$resultCollection->where('team_id',$team->id)->first();
        }
        return $this->successResponse($result,200);
    }

    public function teamMatches($team1_id,$team2_id): \Illuminate\Http\JsonResponse
    {
        $team1=Team::query()->where('team_id',$team1_id)->first();
        if($team1 == null ){
            $team1=Team::query()->findOrFail($team1_id);
        }
        $team2=Team::query()->where('team_id',$team2_id)->first();
        if($team2 == null ){
            $team2=Team::query()->findOrFail($team2_id);
        }
        $match_date=\request()->input('match_date');
        return $this->showAll(MatchResource::collection(Matche::query()->when($match_date,function ($builder) use($match_date){
            return $builder->whereDate('match_date',$match_date);
        })->whereIn('team1_id',[$team1->id,$team2->id])->orWhereIn('team2_id',[$team1->id,$team2->id])->with('competition')->get()->each(function ($builder){
            $builder->home_goals=$builder->events()->getQuery()->where('status','goal')->where('team_id',$builder->team1_id)->get()->count();
            $builder->away_goals=$builder->events()->getQuery()->where('status','goal')->where('team_id',$builder->team2_id)->get()->count();
        }))->collection);
    }
}
