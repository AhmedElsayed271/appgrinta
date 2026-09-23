<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CompitionWithCountryResource;
use App\Http\Resources\MatchResource;
use App\Http\Resources\PostPaginationResource;
use App\Http\Resources\TeamResource;
use App\Models\Competition;
use App\Traits\ApiResponser;
use App\Traits\TablesQuery;

class CompetitionsController extends Controller
{
    use ApiResponser,TablesQuery;
    public function index(): \Illuminate\Http\JsonResponse
    {
        return $this->showAll(CompitionWithCountryResource::collection(Competition::with('country')->get())->collection);
    }
    public function show($id): \Illuminate\Http\JsonResponse
    {
         $competition=Competition::query()->with('country')->where('league_id',$id)->first();
        if($competition == null ){
            $competition=Competition::query()->with('country')->findOrFail($id);
        }
        return $this->successResponse(new CompitionWithCountryResource($competition),200);
    }
    public function posts($id): \Illuminate\Http\JsonResponse
    {
        //league_id
         $competition=Competition::query()->where('league_id',$id)->first();
        if($competition == null ){
            $competition=Competition::query()->findOrFail($id);
        }
        $posts=$competition->posts()->with('category')->get();
        return $this->successResponse(PostPaginationResource::make($this->showAllPagination($posts)),200);
    }
    public function postsd($id): \Illuminate\Http\JsonResponse
    {
         $competition=Competition::query()->where('id',$id)->first();
        if($competition == null ){
            $competition=Competition::query()->findOrFail($id);
        }
        $posts=$competition->posts()->with('category')->get();
        return $this->successResponse(PostPaginationResource::make($this->showAllPagination($posts)),200);
    }

    public function teams($id): \Illuminate\Http\JsonResponse
    {
        $competition=Competition::query()->where('league_id',$id)->first();
        if($competition == null ){
            $competition=Competition::query()->findOrFail($id);
        }
        return $this->showAll(TeamResource::collection($competition->teams()->get())->collection);
    }
    public function teamsdash($id)
    {
        // $competition=Competition::query()->where('id',$id)->where('has_parent',0)->first();
        // if($competition == null ){
            // $competition=Competition::query()->findOrFail($id);
        // }
            // return Competition::find($id);
        $competitions = Competition::find($id);
        $teams = [];
        foreach($competitions->children()->get() as $competition) {
            $teams[] = TeamResource::collection($competition->teams()->get())->collection;
        }
        $teamsArray = [];
        foreach ($teams as $team) {
            foreach($team as $tm) {
                $teamsArray[] = $tm;
            }
        }
        return $this->successResponse(array_values($teamsArray),200);
        // return $this->showAll(TeamResource::collection($competition->teams()->get())->collection);
    }

    public function matches($id): \Illuminate\Http\JsonResponse
    {
        $competition=Competition::query()->where('id',$id)->with('children')->first();
        if($competition == null ){
            $competition=Competition::query()->with('children')->findOrFail($id);
        }
        $match_date=\request()->input('match_date');
        $children=$competition->children->pluck('id')->add($id);
        // return $this->successResponse(MatchPaginationResource::make($this->showAllPagination()),200);

        $order = 'desc';
        if (request()->has('order')) {
            $order = request()->input('order');
        }

        return $this->showAll(MatchResource::collection($competition->matches()->orderBy('match_date', $order)->when($match_date,function ($builder) use($match_date){
            return $builder->whereDate('match_date',$match_date);
        })->whereIn('competition_id',$children->toArray())->with('competition.parent')->get()->each(function ($builder){
            $builder->home_goals=$builder->events()->getQuery()->where('status','goal')->where('team_id',$builder->team1_id)->get()->count();
            $builder->away_goals=$builder->events()->getQuery()->where('status','goal')->where('team_id',$builder->team2_id)->get()->count();
        }))->collection);
    }


    public function points($id): \Illuminate\Http\JsonResponse
    {
        // $season = request()->input('season', null);

        // if ($season == null) {
        //     $settings = Setting::find(1);
        //     $season = $settings->season;
        // }
        // // 2021-2022
        // $season = intval(str_replace(' ','',$season));
        // $completeSeason = $season.'-'. ($season + 1);

        // $competition=Competition::query()->where('league_id',$id)->whereIn('season',[$season, $completeSeason])->first();
        // if($competition == null ){
            $competition=Competition::query()->find($id);
        // }

        $points = $this->getQuery(
            'points',
            [
                'SUM(points.win) AS sum_win',
                'SUM(points.draw) AS sum_draw',
                'SUM(points.loss) AS sum_loss',
                'SUM(points.goals_for) AS sum_goals_for',
                'SUM(points.goals_against) AS sum_goals_against',
                'points.team_id',
                'points.competition_id'
            ],
            [
                ['left','teams','teams.id','=','points.team_id'],
            ],'points.competition_id,points.team_id')->get();

        $teams=$competition->teams()->get();
        $collection=collect();
        foreach ($teams as $key=>$value){
            $team_point=$points->where('team_id',$value->id)->where('competition_id', $competition->id)->first();
            $collection->add([
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
        $collection=$collection->sortBy([
            ['points' , 'desc'],
            ['differance_goals' , 'desc'],
            ['sort' , 'asc'],
            // ['differance_goals' , 'desc'],
        ]);
        //sortByDesc('sum_goals_for');
        return $this->successResponse($collection->sortByDesc('points')->values(),200);

    }

    public function child(Competition $competition)
    {
        return $this->successResponse($competition->children()->get(),200);
    }
}
