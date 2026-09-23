<?php

namespace App\Http\Controllers\Dashboard;

use Carbon\Carbon;
use App\Models\Post;
use App\Models\Team;
use App\Models\Matche;
use App\Models\Country;
use App\Models\Setting;
use App\Models\Competition;
use App\Traits\TablesQuery;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Traits\CustomResponser;
use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse;
use App\Models\CountryTranslation;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\App;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Contracts\View\Factory;
use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\DB;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

class CompetitionsController extends Controller
{
    use CustomResponser,TablesQuery;
    public function __construct()
    {
        $this->middleware('role:admin,competition')->except(['show']);
        $this->middleware('permission:view-competition,full-permissions')->only('index');
        $this->middleware('permission:create-competition,full-permissions')->only(['create','store']);
        $this->middleware('permission:update-competition,full-permissions')->only(['edit','update']);
        $this->middleware('permission:delete-competition,full-permissions')->only(['delete']);
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
            $seasonQuery = $query['season'] ?? null;
            if ($seasonQuery != null) {
                unset($query['season']);
                $season = intval(str_replace(' ','',$seasonQuery));
                $completeSeason = $season.'-'. ($season + 1);
                $data = Competition::query()->with('country')->whereIn('season', [$season, $completeSeason]);
                $items=$this->search($data,Competition::SEARCHFIELDS,$search);
                // return $this->showAll($items->get()->makeVisible('action'));
            }else {
                $items=$this->search(Competition::query()->with('country'),Competition::SEARCHFIELDS,$search);
            }

            return $this->showAll($items->get()->makeVisible('action'));
        }
        $seasons = $this->getCurrentRecordedSeasons();
        $countries=Country::query()->get()->pluck('name','id')->toArray();
        $page_title = __('site.competition.show');
        $page_description = __('site.competition.page_description');

        return view('dashboard.competitions.index',compact('page_title','page_description','countries','seasons'));
    }

    public function getCurrentRecordedSeasons() {
        $com = Competition::query()->whereNotNull('season')->distinct()->pluck('season');
        $seasons = [];

        foreach ($com as $co) {
            $seasons[] = (int)substr((string)$co, 0, 4 );
        }
        return array_unique($seasons);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Application|Factory|View
     */
    public function create()
    {
        $countries=Country::all()->pluck('name','id');
        $page_title = __('site.competition.create');
//        $page_description = __('site.category.description');
        return \view('dashboard.competitions.create',compact('page_title','countries'));
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
        $rules+=[
            'season'=>['required'],
            'country_id'=>['required','exists:countries,id']
        ];
        $request->validate($rules);
        $request_data=$request->except(['has_image','image']);
        $image=$request->file('image');
        $request_data['has_parent']=$request->input('has_children') == '0'? $request->input('has_parent') :'0';
        $request_data['parent_id']=$request->input('has_parent') == 1? $request->input('parent_id'): null;
        Storage::disk('public')->makeDirectory('uploads/competition_images');
        if ($request->hasFile('image')) {
            $request_data['image']=time().'_'.$image->hashname();
            $image->storeAs('public/uploads/competition_images/',$request_data['image']);
        }
        Competition::query()->create($request_data);
        session()->flash('success', __('site.successfully.added'));
        return redirect()->route('dashboard.competitions.index');
    }

    /**
     * Display the specified resource.
     *
     * @param Competition $competition
     * @return JsonResponse
     */
    public function show(string $local, Competition  $competition): JsonResponse
    {
        return response()->json($competition->teams()->get());
    }
    /**
     * Show the form for editing the specified resource.
     *
     * @param Competition $competition
     * @return Application|Factory|View
     */
    public function edit(string $local, Competition $competition)
    {
        $match_teams=$competition->has_children=='0' ? $competition->teams()->get()->pluck('id')->toArray() : [];
        $teams=Team::all()->pluck('name','id');
        $competition=$competition->load('country');
        $countries=Country::all()->pluck('name','id');
        $page_title = __('site.competition.edit');
//        $page_description = __('site.category.description');
        return \view('dashboard.competitions.edit',compact('page_title','competition','countries','match_teams','teams'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param Competition $competition
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, string $local, Competition $competition): \Illuminate\Http\RedirectResponse
    {
        $rules = [
            'teams'=>['array'],
            'teams.*'=>['exists:teams,id']
        ];
//        foreach (LaravelLocalization::getSupportedLocales() as $locale => $properties) {
            $rules += [
                /*$locale*/ 'ar' . '.name' => [
                    'required',
                //    Rule::unique('competition_translations','name')->ignore($competition->id,'competition_id')
                ],
            ];
//        }
        $rules+=[
            'season'=>['required'],
            'country_id'=>['required','exists:countries,id']
        ];
        $request->validate($rules);
        $request_data=$request->except(['has_image','image']);
        $request_data['has_parent']=$request->input('has_children') == '0'? $request->input('has_parent') :'0';
        $request_data['parent_id']=$request->input('has_parent') == 1? $request->input('parent_id'): null;
        $image=$request->file('image');
        Storage::disk('public')->makeDirectory('uploads/competition_images');
        if ($request->hasFile('image')) {
            if ($competition->image != 'default.png'){
                Storage::disk('public')->delete('uploads/competition_images/'.$competition->image);
            }
            $request_data['image']=time().'_'.$image->hashname();
            $image->storeAs('public/uploads/competition_images/',$request_data['image']);
        }
        $competition->update($request_data);
        if (is_array($request->input('teams'))&& $request->input('teams')){
            $competition->teams()->detach();
            if ($request->input('has_children')=='0'){
                $competition->teams()->sync($request->input('teams'));
            }
        }
        session()->flash('success', __('site.successfully.updated'));
        return redirect()->route('dashboard.competitions.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Competition $competition
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(string $local, Competition $competition): \Illuminate\Http\RedirectResponse
    {
        if ($competition->image != 'default.png'){
            Storage::disk('public')->delete('uploads/competition_images/'.$competition->image);
        }
        $competition->delete();
        session()->flash('success', __('site.successfully.deleted'));
        return redirect()->back();
    }

    public function points(string $local, Competition $competition){
        $teams=$competition->teams()->get();
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
                'points'=>$team_point?(($team_point->sum_win*3)+($team_point->sum_draw*1)):0,
                'sort' => $value->sort,
                'id' => $value->id,
            ]);
        }

        $collection=$collection->sortBy([
            ['points' , 'desc'],
            ['differance_goals' , 'desc'],
            ['sort' , 'asc'],
            // ['differance_goals' , 'desc'],
        ]);
        // $collection=$collection->sortByDesc('sum_goals_for');
        // $collection=$collection->sortByDesc('points');
        // $collection=$collection->sortByDesc('differance_goals');

        // $collection=$collection->sortByDesc('sort');
        // $collection=$collection->sortByDesc('differance_goals');
        // $collection=$collection->sortByDesc('points');


        return \view('dashboard.competitions.points',compact('teams','collection'));
    }

    public function loadTeams(string $local, Competition $competition): JsonResponse
    {
        $setting = Setting::find(1);

        if ($competition->league_id){
            $response = Http::withHeaders([
                 'x-rapidapi-key' => $setting->football_key
            ])->get('https://v3.football.api-sports.io/teams?league='.$competition->league_id.'&season=' . $competition->season)->json();

            if (is_array($response['response'])){
                foreach ($response['response'] as $item){
                    $team=$item['team'];
                    $checkTeamExist=Team::query()->where('team_id',$team['id'])->first();
                    $checkTeam=null;
                    if (!$checkTeamExist){
                        $checkTeam = Team::create([
                            'en' => ['name' => $team['name']],
                            'ar' => ['name' => $team['name'] ],
                            'team_id'=>$team['id'],
                            'image'=>$team['logo'],
                        ]);
                    }else{
                        $checkTeam=$checkTeamExist;
                    }
                    // $competition->teams()->syncWithoutDetaching([$checkTeam->id]);
                    DB::table('competition_team')->insert([
                        'team_id' => $checkTeam->id,
                        'competition_id' => $competition->id
                    ]);
                }
            }
        }
        return response()->json('success',200);
    }

    public function changeSort(Request $request){
        $team = Team::find($request->id);
        $up = $team->update([
            'sort' => $request->value,
        ]);
    }


    public function changeCompetitionSort(Request $request){
        $competition = Competition::find($request->id);
        $up = $competition->update([
            'sort' => $request->value,
        ]);
    }

    public function createCompetitionFootballApi(){
        return view('dashboard.competitions.createFootballApi');
    }

    public function storeCompetitionFootballApi(Request $request){
        $leagueId = $request->competition;

        $setting = Setting::find(1);

        $leaguesDatabase = $setting->leagues;
        $leaguesArray = explode(",",$leaguesDatabase);

        if( ! in_array($leagueId,$leaguesArray) ){
            $setting->update([
                'leagues' => $leaguesDatabase . "," . $leagueId
            ]);
        }

        $competition = Competition::where('league_id',$leagueId)->first();
        if($competition){
            $competitionId = $competition->id;
        }else{

            $setting = Setting::find(1);

            $day = date("d");
            $from = date("Y-m-d");

            if($day < 16){

                $date = Carbon::createFromFormat('Y-m-d', date('Y-m-d'));
                $daysToAdd = 16 - $day;
                $date = $date->addDays($daysToAdd);
                $to = substr($date,0,10);
            }else{
                $date = Carbon::createFromFormat('Y-m-d', date('Y-m-d'));
                $daysToAdd = 31 - $day;
                $date = $date->addDays($daysToAdd);
                $to = substr($date,0,10);
            }
            // $to = "";

            $response = Http::withHeaders([
                'x-rapidapi-key' =>"9b9e63115b02a50d22179fb4f1f1b518"
            ])->get('https://v3.football.api-sports.io/fixtures', [
                'timezone' => 'Africa/Cairo',
                'league' => $leagueId,
                'from' => $from,
                'to' => $to,
                'season' => $setting->season,
            ]);

           $data = $response->json($key = "response");
           $data = collect($data);
           foreach($data as $data1){

                if($data->first() == $data1){

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


                    $competition = Competition::create([
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

                    $competitionId = $competition->id;


                }

                $homeTeamId = $data1["teams"]["home"]["id"];
                $team = Team::where('team_id',$homeTeamId)->first();

                if($team){
                    $homeTeamId = $team->id;
                }else{

                    $imageUrl = $data1["teams"]["home"]["logo"];
                    $ext = pathinfo($imageUrl,PATHINFO_EXTENSION);
                    $imageContents = @file_get_contents($imageUrl);
                    $imageName = time() . Str::random(10) . "." . $ext;
                    Storage::disk('public')->put('uploads/team_images/' . $imageName , $imageContents);

                    $team = Team::create([
                        'en' => [
                            'name' => $data1["teams"]["home"]["name"]
                        ],
                        'ar' => [
                            'name' => $data1["teams"]["home"]["name"]
                        ],
                        'team_id' => $data1["teams"]["home"]["id"],
                        'image' => $imageName,
                    ]);
                    $homeTeamId = $team->id;

                }

                $awayTeamId = $data1["teams"]["away"]["id"];
                $team = Team::where('team_id',$awayTeamId)->first();

                if($team){
                    $awayTeamId = $team->id;
                } else{

                    $imageUrl = $data1["teams"]["away"]["logo"];
                    $ext = pathinfo($imageUrl,PATHINFO_EXTENSION);
                    $imageContents = @file_get_contents($imageUrl);
                    $imageName = time() . Str::random(10) . "." . $ext;
                    Storage::disk('public')->put('uploads/team_images/' . $imageName , $imageContents);

                    $team = Team::create([
                        'en' => [
                             'name' => $data1["teams"]["away"]["name"]
                         ],
                         'ar' => [
                             'name' => $data1["teams"]["away"]["name"]
                         ],
                         'team_id' => $data1["teams"]["away"]["id"],
                         'image' => $imageName,
                        ]);
                     $awayTeamId = $team->id;
                }

                $matchesInDatabase = Matche::whereBetween('match_date',[$from,$to])->whereNotNull('fixture_id')->get()->pluck('fixture_id')->toArray();
                $fixtureId = $data1["fixture"]["id"];
                if(! in_array($fixtureId,$matchesInDatabase)){
                    $week = $data1["league"]["round"];
                    $week = (int) filter_var($week, FILTER_SANITIZE_NUMBER_INT);
                    $week = abs($week);

                    $match = Matche::create([
                        'en' => [
                             'location' => $data1["fixture"]["venue"]["name"]
                         ],
                         'ar' => [
                             'location' => $data1["fixture"]["venue"]["name"]
                         ],
                     'fixture_id' => $fixtureId,
                     'team1_id' => $homeTeamId ,
                     'team2_id' => $awayTeamId,
                     'competition_id' => $competitionId,
                     'match_date' => $data1["fixture"]["date"],
                     'week' => $week,
                     'status' => $data1["fixture"]["status"]["short"],
                     ]);
                }

            }
        }
        return redirect()->back();

    }

}
