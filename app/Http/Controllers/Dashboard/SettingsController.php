<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\testtest;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Intervention\Image\Constraint;
use Intervention\Image\Facades\Image;
use Laravel\Ui\Presets\React;

class SettingsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Application|Factory|View|Response
     */
    public function index()
    {
        $setting=Setting::all()->first();
        $page_title = __('site.setting.edit');
        $test = testtest::find(1);
        return view('dashboard.settings.edit',compact('setting','page_title','test'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return Response
     */
    public function store(Request $request, string $locale)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function show(string $local, $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit(string $local, $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param Setting $setting
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, string $local, Setting $setting): \Illuminate\Http\RedirectResponse
    {
        $rules=[
            'name'=> ['required'],
            'firebase_key'=> ['required'],
            'football_key'=> ['required'],
        ];
        $request->validate($rules);
        $request_data=$request->except(['has_image','image']);
        $image=$request->file('image');
        Storage::disk('public')->makeDirectory('uploads/setting_logos');
        if ($request->hasFile('image')) {
            if ($setting->image != 'default.png'){
                Storage::disk('public')->delete('uploads/setting_logos/'.$setting->image);
            }
            $request_data['logo']=time().'_'.$request->file('image')->hashName();
            $request->file('image')->storeAs('public/uploads/setting_logos/',$request_data['logo']);
        }
        if ($request->hasFile('title_icon')) {
            if ($setting->title_icon != 'favicon.ico'){
                Storage::disk('public')->delete('uploads/setting_logos/'.$setting->title_icon);
            }
            $request_data['title_icon']=time().'_'.$request->file('title_icon')->hashName();
            $request->file('title_icon')->storeAs('public/uploads/setting_logos/',$request_data['title_icon']);
        }
        if ($request->hasFile('loader_image')) {
            if ($setting->loader_image != 'loader.png'){
                Storage::disk('public')->delete('uploads/setting_logos/'.$setting->loader_image);
            }
            $request_data['loader_image']=time().'_'.$request->file('loader_image')->hashName();
            $request->file('loader_image')->storeAs('public/uploads/setting_logos/',$request_data['loader_image']);
        }
        $setting->update($request_data);
        session()->flash('success', __('site.successfully.updated'));
        return redirect()->route('dashboard.welcome');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy(string $local, $id)
    {
        //
    }

    public function updateLeaguesAndSeason(Request $request){
        $leagues = $request->leagues;
        $season = $request->season;

        $setting = Setting::find(1);
        $setting->update([
            'leagues' => $leagues,
            'season' => $season
        ]);

        return redirect()->back();

    }
}
