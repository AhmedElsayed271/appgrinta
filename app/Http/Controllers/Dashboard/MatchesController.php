<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Competition;
use App\Models\Country;
use App\Models\Matche;
use App\Models\Team;
use App\Traits\CustomResponser;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

class MatchesController extends Controller
{
    use CustomResponser;

    public function __construct()
    {
        $this->middleware('role:admin,match')->except(['show']);
        $this->middleware('permission:view-match,full-permissions')->only('index');
        $this->middleware('permission:create-match,full-permissions')->only(['create','store']);
        $this->middleware('permission:update-match,full-permissions')->only(['edit','update']);
        $this->middleware('permission:delete-match,full-permissions')->only(['delete']);
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
            $items=$this->search(Matche::query()->with(['team1','team2','competition']),Matche::SEARCHFIELDS,$search);
            return $this->showAll($items->get()->makeVisible('action'));
        }
        $countries=Country::query()->get()->pluck('name','id')->toArray();
        $page_title = __('site.competition.show');
        $page_description = __('site.competition.page_description');
        return view('dashboard.matches.index',compact('page_title','page_description','countries'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Application|Factory|View
     */
    public function create()
    {
        $countries=Country::all()->pluck('name','id');
        $page_title = __('site.match.create');
//        $page_description = __('site.category.description');
        return \view('dashboard.matches.create',compact('page_title','countries'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request, string $locale): \Illuminate\Http\RedirectResponse
    {

        $rules=[
            'match_date'=>['required'],
            'week'=>['required'],
            'status'=>['required'],
            'team1_id'=>['required','exists:teams,id'],
            'team2_id'=>['required','exists:teams,id'],
            'competition_id'=>['required','exists:competitions,id'],
        ];

        $request->validate($rules);
        $request_data=$request->all();
        $request_data['competition_id']=$request->input('competition_child_id')??$request->input('competition_id');
        Matche::query()->create($request_data);
        session()->flash('success', __('site.successfully.added'));
        return redirect()->route('dashboard.matches.index');
    }

    public function show(string $local, Matche $match): JsonResponse
    {
        return response()->json(Team::query()->whereIn('id',[$match->team1_id,$match->team2_id])->get(),200);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Match $match
     * @return Application|Factory|View
     */
    public function edit(string $local, Matche $match)
    {
        $match=$match->load('competition');
        $countries=Country::all()->pluck('name','id');
        $page_title = __('site.competition.edit');
//        $page_description = __('site.category.description');
        return \view('dashboard.matches.edit',compact('page_title','match','countries'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param Match $match
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, string $local, Matche $match): \Illuminate\Http\RedirectResponse
    {
        $rules=[
            'match_date'=>['required'],
            'week'=>['required'],
            'status'=>['required'],
            'team1_id'=>['required','exists:teams,id'],
            'team2_id'=>['required','exists:teams,id'],
            'competition_id'=>['required','exists:competitions,id'],
        ];

        $request->validate($rules);

        $match->update($request->all());
        session()->flash('success', __('site.successfully.updated'));
        return redirect()->route('dashboard.matches.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Match $match
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(string $local, Matche $match): \Illuminate\Http\RedirectResponse
    {
        $match->delete();
        session()->flash('success', __('site.successfully.deleted'));
        return redirect()->back();
    }
}
