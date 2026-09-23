<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Competition;
use App\Models\Country;
use App\Models\Enter;
use App\Models\Matche;
use App\Models\MatchEvent;
use App\Models\PlayerStatistic;
use App\Models\Point;
use App\Models\Post;
use App\Models\Team;
use App\Traits\CustomResponser;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

class MatchEventsController extends Controller
{
    use CustomResponser;

    public function __construct()
    {
        $this->middleware('role:admin,matchEvent');
        $this->middleware('permission:view-matchEvent,full-permissions')->only('index');
        $this->middleware('permission:create-matchEvent,full-permissions')->only(['create','store']);
        $this->middleware('permission:update-matchEvent,full-permissions')->only(['edit','update']);
        $this->middleware('permission:delete-matchEvent,full-permissions')->only(['delete']);
    }
    public function index(string $locale,Matche $match){
        if (\request()->ajax()){
            $query=request()->has('query')? request()->input('query'):[];
            $search=$query['generalSearch']??null;
            $items=$this->search($match->events()->getQuery()->with(['player','team','team']),MatchEvent::SEARCHFIELDS,$search);
            return $this->showAll($items->get()->makeVisible('action'));
        }
        $page_title = __('site.post.show');
        $page_description = __('site.post.page_description');
        return view('dashboard.match_events.index',compact('page_title','page_description','match'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Application|Factory|View
     */
    public function create(string $locale,Matche $match)
    {
        $teams=Team::query()->whereIn('id',[$match->team1_id,$match->team2_id])->get();
        $page_title = __('site.match_event.create');
//        $page_description = __('site.category.description');
        return \view('dashboard.match_events.create',compact('page_title','match','teams'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param Match $match
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request, string $locale, Matche $match): \Illuminate\Http\RedirectResponse
    {
        $rules = [];
//        foreach (LaravelLocalization::getSupportedLocales() as $locale => $properties) {
            $rules += [
//                 /*$locale*/ 'ar' . '.description' => [
//                     'required',
// //                    Rule::unique('post_translations','name')
//                 ]
            ];
//        }

        $rules+=[
            'minute'=>['required'],
            'player_id'=>['nullable','exists:players,id'],
            'team_id'=>['nullable','exists:teams,id'],
//            'match_id'=>['required','exists:matches,id'],
            'status'=>['required',Rule::in(MatchEvent::STATUS)],
        ];
        $request->validate($rules);

        if ($request->input('status') == 'end match'){

        $eventControlArray = MatchEvent::where('match_id',$match->id)->get()->pluck('status')->toArray();
            if(in_array("end match",$eventControlArray)){
                session()->flash('success', "match end added currently");
                return redirect()->route('dashboard.matches.events.index',$match->id);
            }
        }

        $request_data=$request->except(['has_image','image']);
        $request_data['competition_id']=$match->competition_id;
        $request_data['match_id']=$match->id;
        $matchEvent=MatchEvent::query()->create($request_data);
        if ($request->input('player_id')){
            $this->player_statistic($request,$match);
        }
        if ($request->input('status') == 'end match'){

            $first_team_goals=$match->events()->getQuery()->where('status','goal')->where('team_id',$match->team1_id)->get()->count();
            $second_team_goals=$match->events()->getQuery()->where('status','goal')->where('team_id',$match->team2_id)->get()->count();
            if ($first_team_goals>$second_team_goals){
                Point::query()->create([
                    'win'=>'1',
                    'goals_for'=>$first_team_goals,
                    'goals_against'=>$second_team_goals,
                    'team_id'=>$match->team1_id,
                    'competition_id'=>$match->competition_id,
                    'match_id'=>$match->id,
                ]);
                Point::query()->create([
                    'loss'=>'1',
                    'goals_for'=>$second_team_goals,
                    'goals_against'=>$first_team_goals,
                    'team_id'=>$match->team2_id,
                    'competition_id'=>$match->competition_id,
                    'match_id'=>$match->id,
                ]);
            }elseif ($first_team_goals<$second_team_goals){
                Point::query()->create([
                    'loss'=>'1',
                    'goals_for'=>$first_team_goals,
                    'goals_against'=>$second_team_goals,
                    'team_id'=>$match->team1_id,
                    'competition_id'=>$match->competition_id,
                    'match_id'=>$match->id,
                ]);
                Point::query()->create([
                    'win'=>'1',
                    'goals_for'=>$second_team_goals,
                    'goals_against'=>$first_team_goals,
                    'team_id'=>$match->team2_id,
                    'competition_id'=>$match->competition_id,
                    'match_id'=>$match->id,
                ]);
            }else{
                Point::query()->create([
                    'draw'=>'1',
                    'goals_for'=>$first_team_goals,
                    'goals_against'=>$second_team_goals,
                    'team_id'=>$match->team1_id,
                    'competition_id'=>$match->competition_id,
                    'match_id'=>$match->id,
                ]);
                Point::query()->create([
                    'draw'=>'1',
                    'goals_for'=>$second_team_goals,
                    'goals_against'=>$first_team_goals,
                    'team_id'=>$match->team2_id,
                    'competition_id'=>$match->competition_id,
                    'match_id'=>$match->id,
                ]);
            }
            $match_date=$match->match_date;
            $match->update([
                'status'=>'end the match',
                'match_date'=>$match_date,
                ]);
        }
        if ($request->input('status') == 'exit'){
            if ($request->input('player_enter')){
                Enter::query()->create([
                    'player_id'=>$request->input('player_enter'),
                    'match_event_id'=>$matchEvent->id,
                ]);
            }
        }
        session()->flash('success', __('site.successfully.added'));
        return redirect()->route('dashboard.matches.events.index',$match->id);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Match $match
     * @param MatchEvent $matchEvent
     * @return Application|Factory|View
     */
    public function edit(string $local, Matche $match,MatchEvent $event)
    {
        $teams=Team::query()->whereIn('id',[$match->team1_id,$match->team2_id])->get();
        $matchEvent=$event->load(['player','team','team']);
        $page_title = __('site.match_event.edit');
//        $page_description = __('site.category.description');
        return \view('dashboard.match_events.edit',compact('page_title','matchEvent','teams','match'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param Match $match
     * @param MatchEvent $matchEvent
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request,string $local, Matche $match, MatchEvent $event): \Illuminate\Http\RedirectResponse
    {
        $rules = [];
//        foreach (LaravelLocalization::getSupportedLocales() as $locale => $properties) {
        $rules += [
//             /*$locale*/ 'ar' . '.description' => [
//                 'required',
// //                    Rule::unique('post_translations','name')
//             ]
        ];
//        }

        $rules+=[
            'minute'=>['required'],
            'player_id'=>['nullable','exists:players,id'],
            'team_id'=>['nullable','exists:teams,id'],
//            'match_id'=>['required','exists:matches,id'],
            'status'=>['required',Rule::in(MatchEvent::STATUS)],
        ];
        $request->validate($rules);
        if ($request->input('status') == 'end match'){

            $eventControlArray = MatchEvent::where('match_id',$match->id)->get()->pluck('status')->toArray();
                if(in_array("end match",$eventControlArray)){
                    session()->flash('success', "match end added currently");
                    return redirect()->route('dashboard.matches.events.index',$match->id);
                }
        }
        $request_data=$request->all();
        if ($request->input('player_id') !=null || $event->player_id != $request->input('player_id') ){
            $this->player_statistic($request,$match);
        }
        $event->update($request_data);
        session()->flash('success', __('site.successfully.updated'));
        return redirect()->route('dashboard.matches.events.index',$match->id);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Match $match
     * @param MatchEvent $matchEvent
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(string $local, Matche $match,MatchEvent $event): \Illuminate\Http\RedirectResponse
    {

        if($event->status == "end match"){
           $matchId = $event->match_id;
           $points = Point::where('match_id',$matchId)->get();
           foreach($points as $point){
            $point->delete();
           }
        }
        $event->delete();
        session()->flash('success', __('site.successfully.deleted'));
        return redirect()->back();
    }

    protected function player_statistic(Request $request, Matche $match) {
        $playerStatistic=PlayerStatistic::query()->where('player_id',$request->input('player_id'))->where('competition_id',$match->competition_id)->first();
        switch ($request->input('status')){
            case 'yellow card':
                if ($playerStatistic)
                {
                    $yellow_card=(int)$playerStatistic->yellow_card;
                    $playerStatistic->update(['yellow_card'=>($yellow_card+1)]);
                }else{
                    PlayerStatistic::query()->create([
                        'player_id'=>$request->input('player_id'),
                        'competition_id'=>$match->competition_id,
                        'yellow_card'=>1,
                        'red_card'=>0,
                        'goal'=>0,
                    ]);
                }
                break;
            case 'red card':
                if ($playerStatistic)
                {
                    $red_card=(int)$playerStatistic->red_card;
                    $playerStatistic->update(['red_card'=>($red_card+1)]);
                }else{
                    PlayerStatistic::query()->create([
                        'player_id'=>$request->input('player_id'),
                        'competition_id'=>$match->competition_id,
                        'yellow_card'=>0,
                        'red_card'=>1,
                        'goal'=>0,
                    ]);
                }
                break;
            case 'goal':
                if ($playerStatistic)
                {
                    $goal=(int)$playerStatistic->goal;
                    $playerStatistic->update(['goal'=>($goal+1)]);
                }else{
                    PlayerStatistic::query()->create([
                        'player_id'=>$request->input('player_id'),
                        'competition_id'=>$match->competition_id,
                        'yellow_card'=>0,
                        'red_card'=>0,
                        'goal'=>1,
                    ]);
                }
                break;
            default:
                break;
        }
    }
}
