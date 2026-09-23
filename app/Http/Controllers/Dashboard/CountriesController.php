<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Competition;
use App\Models\Country;
use App\Traits\CustomResponser;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

class CountriesController extends Controller
{
    use CustomResponser;
    public function __construct()
    {
        $this->middleware('role:admin,country')->except(['show']);
        $this->middleware('permission:view-country,full-permissions')->only('index');
        $this->middleware('permission:create-country,full-permissions')->only(['create','store']);
        $this->middleware('permission:update-country,full-permissions')->only(['edit','update']);
        $this->middleware('permission:delete-country,full-permissions')->only(['delete']);
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
            $items=$this->search(Country::query(),Country::SEARCHFIELDS,$search);
            return $this->showAll($items->get()->makeVisible('action'));
        }
        $page_title = __('site.country.show');
        $page_description = __('site.category.page_description');
        return view('dashboard.countries.index',compact('page_title','page_description'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Application|Factory|View|\Illuminate\Http\Response
     */
    public function create()
    {
        $page_title = __('site.country.create');
//        $page_description = __('site.category.description');
        return \view('dashboard.countries.create',compact('page_title'));
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
            $rules += [/*$locale*/ 'ar' . '.name' => ['required',Rule::unique('country_translations','name')]];
//        }
        $rules+=[
            'image'=>['required','image']
        ];
        $request->validate($rules);
        $request_data=$request->except(['has_image','image']);
        if ($request->hasFile('image')){
            $request_data['image']=time().'_'.$request->file('image')->hashName();
            $request->file('image')->storeAs('public/uploads/country_images/',$request_data['image']);
        }
        Country::create($request_data);
        session()->flash('success', __('site.successfully.added'));
        return redirect()->route('dashboard.countries.index');
    }

    /**
     * Display the specified resource.
     *
     * @param Country $country
     * @return JsonResponse
     */
    public function show(string $local, Country $country): JsonResponse
    {
        if (request()->input('has_children') == '1'){
            return response()->json(Competition::query()->where('country_id',$country->id)->where('has_children','1')->get());
        }
        return response()->json($this->showAllWithoutPagination(Competition::query()->where('country_id',$country->id)->get()));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Country $country
     * @return Application|Factory|View|\Illuminate\Http\Response
     */
    public function edit(string $local, Country $country)
    {
        $page_title = __('site.country.edit');
//        $page_description = __('site.category.description');
        return \view('dashboard.countries.edit',compact('page_title','country'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param Country $country
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, string $local, Country $country): \Illuminate\Http\RedirectResponse
    {
        $rules = [];
//        foreach (LaravelLocalization::getSupportedLocales() as $locale => $properties) {
            $rules += [
                /*$locale*/ 'ar' . '.name' =>
                    [
                        'required',
                        Rule::unique('country_translations','name')->ignore($country->id,'country_id')
                    ]
            ];
//        }
        $rules+=[
            'image'=>['image']
        ];
        $request_data=$request->except(['has_image','image']);
        if ($request->hasFile('image')){
            if ($country->image != 'default.svg'){
                Storage::disk('public')->delete('uploads/country_images/'.$country->image);
            }
            $request_data['image']=time().'_'.$request->file('image')->hashName();
            $request->file('image')->storeAs('public/uploads/country_images/',$request_data['image']);
        }
        $request->validate($rules);
        $country->update($request_data);
        session()->flash('success', __('site.successfully.updated'));
        return redirect()->route('dashboard.countries.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Country $country
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function destroy(string $local, Country $country): \Illuminate\Http\RedirectResponse
    {

        $country->delete();
        session()->flash('success', __('site.successfully.deleted'));
        return redirect()->route('dashboard.countries.index');
    }
}
