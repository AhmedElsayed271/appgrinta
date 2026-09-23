<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Competition;
use App\Models\Post;
use App\Models\Team;
use App\Traits\CustomResponser;
use App\Traits\Notify;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

class PostsController extends Controller
{
    use CustomResponser, Notify;

    public function __construct()
    {
        $this->middleware('role:admin,post')->except(['show']);
        $this->middleware('permission:view-post,full-permissions')->only('index');
        $this->middleware('permission:create-post,full-permissions')->only(['create','store']);
        $this->middleware('permission:update-post,full-permissions')->only(['edit','update']);
        $this->middleware('permission:delete-post,full-permissions')->only(['delete']);
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
            $items=$this->search(Post::query(),Post::SEARCHFIELDS,$search);
            return $this->showAll($items->get()->load('category')->makeVisible('action'));
        }
        $main_Categories=Category::query()->where('parent_id',null)->get()->pluck('name','id');
        $page_title = __('site.post.show');
        $page_description = __('site.post.page_description');
        return view('dashboard.posts.index',compact('page_title','page_description','main_Categories'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Application|Factory|View|Response
     */
    public function create()
    {
        $main_Categories=Category::query()->where('parent_id',null)->get()->pluck('name','id');
        // $teams=Team::query()->get()->pluck('name','id');
        $teams=Team::query()->get();
        // return $teams;
        $competitions=Competition::query()->get()->pluck('name','id');
        $page_title = __('site.post.create');
//        $page_description = __('site.category.description');
        return \view('dashboard.posts.create',compact('page_title','main_Categories','competitions','teams'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request, string $locale): \Illuminate\Http\RedirectResponse
    {
        $rules = [
            'ar.name'        => ['required'],
            'ar.description' => ['required'],
            'category_id'    => ['nullable', 'exists:categories,id'],
            'parent_id'      => ['exists:categories,id'],
            'competitions.*' => ['exists:competitions,id'],
            'teams.*'        => ['exists:teams,id'],
//            'youtube_link' => ['nullable'],
        ];

        $request->validate($rules);
        $request_data=$request->except(['has_image','image']);
        $image=$request->file('image');
        $request_data['category_id']=$request->input('category_id')??$request->input('parent_id');
        $request_data['user_id']=auth()->user()->getAuthIdentifier();
        if(isset($request->featured) && $request->featured == "on"){
            $request_data['featured'] = 1;
        }
        Storage::disk('public')->makeDirectory('uploads/post_images');
        if ($request->hasFile('image')) {
            $request_data['image']=time().'_'.$image->hashname();
            $image->storeAs('public/uploads/post_images/',$request_data['image']);
        }
        $post=Post::query()->create($request_data);
        if (is_array($request->input('competitions'))&& $request->input('competitions')){
            $post->competitions()->sync($request->input('competitions'));
        }
        if (is_array($request->input('teams'))&& $request->input('teams')){
            $post->teams()->sync($request->input('teams'));
        }

        // ── Auto Notification ─────────────────────────────────────────────────────
        $this->sendPostNotification($post);

        session()->flash('success', __('site.successfully.added'));
        return redirect()->route('dashboard.posts.index');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Post $post
     * @return Application|Factory|View
     */
    public function edit(string $local, Post $post)
    {
        $post_competitions=DB::table('post_competition')->where('post_id',$post->id)->get()->pluck('competition_id')->toArray();
        $post_teams=DB::table('post_team')->where('post_id',$post->id)->get()->pluck('team_id')->toArray();
        $teams=Team::query()->get()->pluck('name','id');
        $competitions=Competition::query()->get()->pluck('name','id');
        $post=$post->load('category');
        $main_Categories=Category::query()->where('parent_id',null)->get()->pluck('name','id');
        $page_title = __('site.category.edit');
//        $page_description = __('site.category.description');
        return \view('dashboard.posts.edit',compact('page_title','post','main_Categories','post_competitions','post_teams','teams','competitions'));
    }

    public function getTeam(string $locale, Team $team) {
        return $team;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param Post $post
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, string $locale, Post $post): \Illuminate\Http\RedirectResponse
    {
        $rules = [];
//        foreach (LaravelLocalization::getSupportedLocales() as $locale => $properties) {
            $rules += [
                /*$locale*/ 'ar' . '.name' => [
                    'required',
//                    Rule::unique('post_translations','name')->ignore($post->id,'post_id')
                ],
                /*$locale*/ 'ar' . '.description' => [
                    'required',
//                    Rule::unique('post_translations','description')->ignore($post->id,'post_id')
                ]
            ];
//        }
        $rules+=['category_id'=>['nullable','exists:categories,id']];
        $rules+=['parent_id'=>['exists:categories,id']];
        $rules+=['teams.*'=>['exists:teams,id']];
        $rules+=['competitions.*'=>['exists:competitions,id']];
        $request->validate($rules);
        $request_data=$request->except(['has_image','image']);
        $request_data['category_id']=$request->input('category_id')??$request->input('parent_id');
        //$request_data['user_id']=auth()->user()->getAuthIdentifier();
        $image=$request->file('image');
        if(isset($request->featured) && $request->featured == "on"){
            $request_data['featured'] = 1;
        }else{
            $request_data['featured'] = 0;
        }
        Storage::disk('public')->makeDirectory('uploads/post_images');
        if ($request->hasFile('image')) {
            if ($post->image != 'default.png'){
                Storage::disk('public')->delete('uploads/post_images/'.$post->image);
            }
            $request_data['image']=time().'_'.$image->hashname();
            $image->storeAs('public/uploads/post_images/',$request_data['image']);
        }
        $post->update($request_data);
        if (is_array($request->input('competitions'))&& $request->input('competitions')){
            $post->competitions()->sync($request->input('competitions'));
        }
        if (is_array($request->input('teams'))&& $request->input('teams')){
            $post->teams()->sync($request->input('teams'));
        }
        session()->flash('success', __('site.successfully.updated'));
        return redirect()->route('dashboard.posts.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Category $category
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function destroy(string $locale, Post $post): \Illuminate\Http\RedirectResponse
    {
        if ($post->image != 'default.png'){
            Storage::disk('public')->delete('uploads/post_images/'.$post->image);
        }
        $post->delete();
        session()->flash('success', __('site.successfully.deleted'));
        return redirect()->back();
    }

    public function changePostSort(Request $request, string $locale){
        $competition = Post::find($request->id);
        $up = $competition->update([
            'sort' => $request->value,
        ]);
    }

    public function destroyAll(Request $request, string $locale)
    {

        $posts = Post::whereIn('id', $request->ids)->get();
        foreach ($posts as $post) {
            if ($post->image != 'default.png'){
                Storage::disk('public')->delete('uploads/post_images/'.$post->image);
            }
            $post->delete();
        }
        session()->flash('success', __('site.successfully.deleted'));
        return response()->json(['success' => true],200);
    }

    public function featureAll(Request $request, string $locale)
    {
        $posts = Post::whereIn('id', $request->ids)->get();
        foreach ($posts as $post) {
            $post->featured = 1;
            $post->update();
        }
        session()->flash('success', __('site.successfully.updated'));
        return response()->json(['success' => true],200);
    }

    public function unfeatureAll(Request $request, string $locale)
    {
        $posts = Post::whereIn('id', $request->ids)->get();
        foreach ($posts as $post) {
            $post->featured = 0;
            $post->update();
        }
        session()->flash('success', __('site.successfully.updated'));
        return response()->json(['success' => true],200);
    }

    public function assignAll(Request $request, string $locale)
    {
        // return $request;
        $posts = Post::whereIn('id', $request->ids)->get();
        foreach ($posts as $post) {
            $post->user_id = $request->user_id;
            $post->update();
        }

        session()->flash('success', __('site.successfully.updated'));
        return response()->json(['success' => true],200);
    }

    /**
     * Send push notification to all clients when a new post is created.
     */
    private function sendPostNotification(Post $post): void
    {
        try {
            $tokens_en = array_filter(array_unique(array_merge(
                \App\Models\Client::where('locale', 'en')->whereNotNull('fb_token')->pluck('fb_token')->toArray(),
                \App\Models\Guest::tokensEn()
            )));

            $tokens_ar = array_filter(array_unique(array_merge(
                \App\Models\Client::where('locale', 'ar')->whereNotNull('fb_token')->pluck('fb_token')->toArray(),
                \App\Models\Guest::tokensAr()
            )));

            $imageUrl = $post->image
                ? asset('storage/uploads/post_images/' . $post->image)
                : '';

            $notify = [
                'type' => 'post',
                'id' => (string)$post->id,
            ];

            // English
            if (!empty($tokens_en)) {
                $titleEn = $post->translate('en')?->name ?? $post->translate('ar')?->name ?? 'New Post';
                $bodyEn = $post->translate('en')?->description
                    ? substr(strip_tags($post->translate('en')->description), 0, 100) . '...'
                    : '';

                $this->topicNotifyByFirebaseTokens($tokens_en, [
                    'title' => $titleEn,
                    'body' => $bodyEn,
                    'image' => $imageUrl,
                    'notify' => $notify,
                ]);
            }

            // Arabic
            if (!empty($tokens_ar)) {
                $titleAr = $post->translate('ar')?->name ?? $post->translate('en')?->name ?? 'منشور جديد';
                $bodyAr = $post->translate('ar')?->description
                    ? substr(strip_tags($post->translate('ar')->description), 0, 100) . '...'
                    : '';

                $this->topicNotifyByFirebaseTokens($tokens_ar, [
                    'title' => $titleAr,
                    'body' => $bodyAr,
                    'image' => $imageUrl,
                    'notify' => $notify,
                ]);
            }

        } catch (\Exception $e) {
            // Don't fail the post creation if notification fails
            \Illuminate\Support\Facades\Log::error('Auto post notification failed', [
                'post_id' => $post->id,
                'message' => $e->getMessage(),
            ]);
        }
    }

}
