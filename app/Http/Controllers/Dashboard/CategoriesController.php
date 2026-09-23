<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Country;
use App\Traits\CustomResponser;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Intervention\Image\Constraint;
use Intervention\Image\Facades\Image;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

class CategoriesController extends Controller
{
    use CustomResponser;

    public function __construct()
    {
        $this->middleware('role:admin,category')->except(['show']);
        $this->middleware('permission:view-category,full-permissions')->only('index');
        $this->middleware('permission:create-category,full-permissions')->only(['create','store']);
        $this->middleware('permission:update-category,full-permissions')->only(['edit','update']);
        $this->middleware('permission:delete-category,full-permissions')->only(['delete']);
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
            $items=$this->search(Category::query(),Category::SEARCHFIELDS,$search);
            return $this->showAll($items->get()->load('parent')->makeVisible('action'));
        }
        $main_Categories=Category::query()->where('parent_id',null)->get()->pluck('name','id');
        $page_title = __('site.category.show');
        $page_description = __('site.category.page_description');
        return view('dashboard.categories.index',compact('page_title','page_description','main_Categories'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Application|Factory|View|Response
     */
    public function create()
    {
        $main_Categories=Category::query()->where('parent_id',null)->get()->pluck('name','id');
        $page_title = __('site.category.create');
//        $page_description = __('site.category.description');
        return \view('dashboard.categories.create',compact('page_title','main_Categories'));
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
        $rules+=['parent_id'=>['nullable','exists:categories,id']];
        $request->validate($rules);
        $request_data=$request->except(['has_image','image']);
        $image=$request->file('image');
        $request_data['parent_id']=$request->input('parent_id')??null;
        Storage::disk('public')->makeDirectory('uploads/category_images');
        if ($request->hasFile('image')) {
            $request_data['image']=time().'_'.$image->hashname();
            $image->storeAs('public/uploads/category_images/',$request_data['image']);
        }
        Category::query()->create($request_data);
        session()->flash('success', __('site.successfully.added'));
        return redirect()->route('dashboard.categories.index');
    }

    /**
     * Display the specified resource.
     *
     * @param Category $category
     * @return JsonResponse
     */
    public function show(string $local, Category $category): JsonResponse
    {
        return  \response()->json($category->children()->get());
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Category $category
     * @return Application|Factory|View|Response
     */
    public function edit(string $local, Category $category)
    {
        $main_Categories=Category::query()->where('parent_id',null)->where('id','<>',$category->id)->get()->pluck('name','id');
        $page_title = __('site.category.edit');
//        $page_description = __('site.category.description');
        return \view('dashboard.categories.edit',compact('page_title','category','main_Categories'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param Category $category
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, string $local, Category $category): \Illuminate\Http\RedirectResponse
    {
        $rules = [];
//        foreach (LaravelLocalization::getSupportedLocales() as $locale => $properties) {
            $rules += [
                /*$locale*/ 'ar' . '.name' => [
                    'required',
//                    Rule::unique('category_translations','name')->ignore($category->id,'category_id')
                ]
            ];
//        }
        $rules+=['parent_id'=>['nullable','exists:categories,id']];
        $request->validate($rules);
        $request_data=$request->except(['has_image','image']);
        $request_data['parent_id']=$request->input('parent_id')??null;
        $image=$request->file('image');
        Storage::disk('public')->makeDirectory('uploads/category_images');
        if ($request->hasFile('image')) {
            if ($category->image != 'default.png'){
                Storage::disk('public')->delete('uploads/category_images/'.$category->image);
            }
            $request_data['image']=time().'_'.$image->hashname();
            Image::make($request->file('image'))
                ->resize(800, 800, function (Constraint $constraint) {
                    $constraint->aspectRatio();
                })
                ->save(storage_path('app/public/uploads/category_images/'). $request_data['image']);
        }
        $category->update($request_data);
        session()->flash('success', __('site.successfully.updated'));
        return redirect()->route('dashboard.categories.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Category $category
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function destroy(string $local, Category $category): \Illuminate\Http\RedirectResponse
    {
        if ($category->children()->get()->count() > 0  || $category->posts()->get()->count() > 0){
            session()->flash('error', 'Can\'nt delete this item');
            return redirect()->back();
        }
        if ($category->image != 'default.png'){
            Storage::disk('public')->delete('uploads/category_images/'.$category->image);
        }
        $category->delete();
        session()->flash('success', __('site.successfully.deleted'));
        return redirect()->back();
    }
}
