<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Competition;
use App\Models\Country;
use App\Models\Player;
use App\Models\Team;
use App\Traits\CustomResponser;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

class PlayersController extends Controller
{
    use CustomResponser;

    public function __construct()
    {
        $this->middleware('role:admin,player')->except(['show']);
        $this->middleware('permission:view-player,full-permissions')->only('index');
        $this->middleware('permission:create-player,full-permissions')->only(['create','store']);
        $this->middleware('permission:update-player,full-permissions')->only(['edit','update']);
        $this->middleware('permission:delete-player,full-permissions')->only(['delete']);
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
            $items=$this->search(Player::query(),Player::SEARCHFIELDS,$search);
            return $this->showAll($items->with('team')->get()->makeVisible('action'));
        }
        $countries=Country::all()->pluck('name','id');
        $page_title = __('site.player.show');
        $page_description = __('site.player.page_description');
        return view('dashboard.players.index',compact('page_title','page_description','countries'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Application|Factory|View|Response
     */
    public function create()
    {
        $countries=Country::all()->pluck('name','id');
        $page_title = __('site.player.create');
//        $page_description = __('site.category.description');
        return \view('dashboard.players.create',compact('page_title','countries'));
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
            $rules += [
                // /*$locale*/ 'ar' . '.first_name' => ['required'],
//                /*$locale*/ 'ar' . '.last_name' => ['required'],
            ];
//        }
        $rules+=[
            'team_id'=>['required','exists:teams,id'],
            //'current_value'=>['required',],
          //  'twitter_account'=>['required',],
            'position'=>['required',],
        ];
        $request->validate($rules);
        $request_data=$request->except(['has_image','image']);
        $image=$request->file('image');
        Storage::disk('public')->makeDirectory('uploads/players_images');
        if ($request->hasFile('image')) {
            $request_data['image']=time().'_'.$image->hashname();
            $image->storeAs('public/uploads/players_images/',$request_data['image']);
        }
        Player::query()->create($request_data);
        session()->flash('success', __('site.successfully.added'));
        return redirect()->route('dashboard.players.index');
    }


    public function show(string $local, Player $player): JsonResponse
    {
        $team=Team::query()->where('id',$player->team_id)->first();
        $competitions=$team->competitions()->with(['country'])->get();
        $competition=null;
        $teams=[];
        if (count($competitions) != 0){
            $competition=$competitions->first()->load('teams');
            $teams=$competition->teams;
        }
        $country_id=$competition == null ? null : $competition->country_id;
        $country_competitions=$country_id==null?[]:Competition::query()->where('country_id',$country_id)->get();
        return \response()->json([
            'country_id'=>$competition == null ? null : $competition->country_id,
            'competition_id'=>$competition == null ? null : $competition->id,
            'competitions'=>$country_competitions,
            'teams'=>$teams
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Player $player
     * @return Application|Factory|View
     */
    public function edit(string $local, Player $player)
    {
        $player=$player->load(['team']);
        $countries=Country::all()->pluck('name','id');
        $page_title = __('site.player.edit');
//        $page_description = __('site.category.description');
        return \view('dashboard.players.edit',compact('page_title','player','countries'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param Player $player
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, string $local, Player $player): \Illuminate\Http\RedirectResponse
    {
        $rules = [];
//        foreach (LaravelLocalization::getSupportedLocales() as $locale => $properties) {
            $rules += [
                // /*$locale*/ 'ar' . '.first_name' => ['required'],
//                /*$locale*/ 'ar' . '.last_name' => ['required'],
            ];
//        }
        $rules+=[
            'team_id'=>['required','exists:teams,id'],
            //'current_value'=>['required',],
            //'twitter_account'=>['required',],
            'position'=>['required',],
        ];
        $request->validate($rules);
        $request_data=$request->except(['has_image','image']);
        $image=$request->file('image');
        Storage::disk('public')->makeDirectory('uploads/players_images');
        if ($request->hasFile('image')) {
            if ($player->image != 'default.png'){
                Storage::disk('public')->delete('uploads/players_images/'.$player->image);
            }
            $request_data['image']=time().'_'.$image->hashname();
            $image->storeAs('public/uploads/players_images/',$request_data['image']);
        }
        $player->update($request_data);
        session()->flash('success', __('site.successfully.updated'));
        return redirect()->route('dashboard.players.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Player $player
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(string $local, Player $player): \Illuminate\Http\RedirectResponse
    {
        if ($player->image != 'default.png'){
            Storage::disk('public')->delete('uploads/players_images/'.$player->image);
        }
        $player->delete();
        session()->flash('success', __('site.successfully.deleted'));
        return redirect()->back();
    }

    // public function destroyAll(Request $request)
    // {
    //     $players = Player::whereIn('id', $request->ids)->get();
    //     foreach ($players as $player) {
    //         if ($player->image != 'default.png'){
    //             Storage::disk('public')->delete('uploads/post_images/'.$post->image);
    //         }
    //         $player->delete();
    //     }
    //     session()->flash('success', __('site.successfully.deleted'));
    //     return response()->json(['success' => true],200);
    // }

}
