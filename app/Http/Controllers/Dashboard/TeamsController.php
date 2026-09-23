<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Competition;
use App\Models\Country;
use App\Models\Player;
use App\Models\Team;
use App\Models\Setting;

use App\Traits\CustomResponser;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

class TeamsController extends Controller
{
    use CustomResponser;
    public function __construct()
    {
        $this->middleware('role:admin,team')->except(['show']);
        $this->middleware('permission:view-team,full-permissions')->only('index');
        $this->middleware('permission:create-team,full-permissions')->only(['create','store']);
        $this->middleware('permission:update-team,full-permissions')->only(['edit','update']);
        $this->middleware('permission:delete-team,full-permissions')->only(['delete']);
    }

    /**
     * Display a listing of the resource.
     *
     * @return Application|Factory|View|JsonResponse
     */
    public function index()
    {
        if (\request()->ajax()){
            $query=request()->has('query')? request()->input('query'):[];
            $search=$query['generalSearch']??null;
            $competition_id=request()->input('query.competition_id')??null;
            $items=$this->search(
                Team::query()->when($competition_id,
                    function ( $q) use ($competition_id){
                        return $q->whereHas('competitions', function($query) use ($competition_id) {
                            $query->where('competitions.id', $competition_id);
                        });
                    }),Team::SEARCHFIELDS,$search);
            return $this->showAll($items->get()->makeVisible('action'),['competition_id']);
        }
        $countries=Country::query()->get()->pluck('name','id')->toArray();
        $page_title = __('site.team.show');
        $page_description = __('site.team.page_description');
        return view('dashboard.teams.index',compact('page_title','page_description','countries'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Application|Factory|View
     */
    public function create()
    {
        $countries=Country::all()->pluck('name','id');
        $page_title = __('site.team.create');
//        $page_description = __('site.category.description');
        return \view('dashboard.teams.create',compact('page_title','countries'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request, string $locale): \Illuminate\Http\RedirectResponse
    {
        $rules = [];
//        foreach (LaravelLocalization::getSupportedLocales() as $locale => $properties) {
            $rules += [/*$locale*/ 'ar' . '.name' => ['required']];
//        }
        $request->validate($rules);
        $request_data=$request->except(['has_image','image']);
        $image=$request->file('image');
        Storage::disk('public')->makeDirectory('uploads/team_images');
        if ($request->hasFile('image')) {
            $request_data['image']=time().'_'.$image->hashname();
            $image->storeAs('public/uploads/team_images/',$request_data['image']);
        }
        Team::query()->create($request_data);
        session()->flash('success', __('site.successfully.added'));
        return redirect()->route('dashboard.teams.index');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Team $team
     * @return Application|Factory|View
     */
    public function edit(string $local, Team $team)
    {
        $countries=Country::all()->pluck('name','id');
        $page_title = __('site.team.edit');
//        $page_description = __('site.category.description');
        return \view('dashboard.teams.edit',compact('page_title','team','countries'));
    }

    public function show(string $local, Team $team): JsonResponse
    {
        return response()->json(Player::query()->where('team_id',$team->id)->get());
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param Team $team
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, string $local, Team $team): \Illuminate\Http\RedirectResponse
    {
        $rules = [];
//        foreach (LaravelLocalization::getSupportedLocales() as $locale => $properties) {
            $rules += [
                /*$locale*/ 'ar' . '.name' => [
                    'required',
                    //Rule::unique('team_translations','name')->ignore($team->id,'team_id')
                ],
            ];
//        }
        $request->validate($rules);
        $request_data=$request->except(['has_image','image']);
        $image=$request->file('image');
        Storage::disk('public')->makeDirectory('uploads/team_images');
        if ($request->hasFile('image')) {
            if ($team->image != 'default.png'){
                Storage::disk('public')->delete('uploads/team_images/'.$team->image);
            }
            $request_data['image']=time().'_'.$image->hashname();
            $image->storeAs('public/uploads/team_images/',$request_data['image']);
        }
        $team->update($request_data);
        session()->flash('success', __('site.successfully.updated'));
        return redirect()->route('dashboard.teams.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Category $category
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function destroy(string $local, Team $team): \Illuminate\Http\RedirectResponse
    {
        if ($team->players()->count() > 0 ){
            session()->flash('error', 'Can\'nt delete this item');
            return redirect()->back();
        }
        if ($team->image != 'default.png'){
            Storage::disk('public')->delete('uploads/team_images/'.$team->image);
        }
        $team->delete();
        session()->flash('success', __('site.successfully.deleted'));
        return redirect()->back();
    }

    public function loadPlayers(Team $team){
        $team_id= $team->id;
        $team_id = Team::where('id',$team_id)->first()->team_id;
        $total=$this->playersWithTeam(1,$team_id);
        if (is_numeric($total))
        {
            for ($i=2;$i<=$total;$i++){
                $this->playersWithTeam($i,$team_id);
            }
        }
        return response()->json('success',200);

    }

    protected function playersWithTeam(int $page,$team){
        $setting = Setting::find(1);

        $response = Http::withHeaders([
                 'x-rapidapi-key' => $setting->football_key
        ])->get('https://v3.football.api-sports.io/players?team='.(string)$team.'&season=' . $setting->season . '&page='.$page)->json();
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
                    $checkPlayer = Player::query()->create([
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
                        'current_value'=>null,
                        'position'=>$position,
                        'twitter_account'=>null,
                        'image'=>$player['photo'],
                    ]);
                }

                if ($checkPlayer->football_team_id != null) {
                    if ($checkPlayer->football_team_id != $team['id']) {
                        $checkPlayer->update([
                            'team_id' => $checkTeam->id,
                            'football_team_id'=>$team['id'],
                        ]);
                    }
                }
            }
        }
        return $response['paging']['total'];
    }
}
