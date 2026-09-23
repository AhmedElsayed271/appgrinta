<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Traits\CustomResponser;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Intervention\Image\Constraint;
use Intervention\Image\Facades\Image;

class UsersController extends Controller
{
    use CustomResponser;
    public function __construct()
    {
        $this->middleware('role:admin,user')->except(['show']);
        $this->middleware('permission:view-user,full-permissions')->only('index');
        $this->middleware('permission:create-user,full-permissions')->only(['create','store']);
        $this->middleware('permission:update-user,full-permissions')->only(['edit','update']);
        $this->middleware('permission:delete-user,full-permissions')->only(['destroy']);
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

            $items=$this->search(User::query(),User::SEARCHFIELDS,$search);
            return $this->showAll($items->get());
        }
        $page_title = __('site.user.show');
        $page_description = __('site.user.page_description');
        return view('dashboard.users.index',compact('page_title','page_description'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Application|Factory|View|Response
     */
    public function create()
    {
        $roles=Role::all()->load('permissions');
        $page_title = __('site.user.create');
//        $page_description = __('site.category.description');
        return \view('dashboard.users.create',compact('page_title','roles'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return RedirectResponse
     */
    public function store(Request $request, string $locale): RedirectResponse
    {
        //dd($request->all());
        $rules=[
            'first_name' => ['required', 'string', 'max:255'],
            //'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'phone' => ['required', 'string', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'image'=>['image','max:5120']
        ];
        $request->validate($rules);
        $request_data=$request->except(['has_image','image']);
        $request_data['password']=Hash::make($request->input('password'));
        $image=$request->file('image');
        Storage::disk('public')->makeDirectory('uploads/user_images');
        if ($request->hasFile('image')) {
            $request_data['image']=time().'_'.$image->hashname();
            Image::make($request->file('image'))
                ->resize(800, 800, function (Constraint $constraint) {
                    $constraint->aspectRatio();
                })
                ->save(storage_path('app/public/uploads/user_images/'). $request_data['image']);
        }
        $user=User::create($request_data);

        if($request->roles != null){
            foreach ($request->roles as $role) {
                $user->roles()->attach($role);
                $user->save();
            }
        }
        if($request->permissions != null){
            foreach ($request->permissions as $permission) {
                $user->permissions()->attach($permission);
                $user->save();
            }
        }
        session()->flash('success', __('site.successfully.added'));
        return redirect()->route('dashboard.users.index');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param User $user
     * @return Application|Factory|View|Response
     */
public function edit(string $local, $id)
{


    $user = User::findOrFail($id);

    $roles = Role::with('permissions')->get();
    $all_permissions = $user->permissions()->pluck('id')->toArray();
    $all_roles = $user->roles()->pluck('id')->toArray();
    $page_title = __('site.user.edit');

    return view('dashboard.users.edit', compact('user', 'roles', 'all_permissions', 'all_roles', 'page_title'));
}
    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param User $user
     * @return RedirectResponse
     */
    public function update(Request $request, string $local,  User $user): RedirectResponse
    {

        $rules=[
            'first_name' => ['required', 'string', 'max:255'],
            //'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users','email')->ignore($user->id,'id')],
            'phone' => ['required', 'string', 'max:255', Rule::unique('users','phone')->ignore($user->id,'id')],
            'password' => ['string','nullable', 'min:8', 'confirmed'],
            'image'=>['image','max:5120']
        ];
        $request->validate($rules);
        $request_data=$request->except(['has_image','image','password']);
        if ($request->input('password') != null){
            $request_data['password']=Hash::make($request->input('password'));
        }
        $image=$request->file('image');
        Storage::disk('public')->makeDirectory('uploads/user_images');
        if ($request->hasFile('image')) {
            if ($user->image != 'default.png')
            {
                Storage::disk('public')->delete('uploads/user_images/'.$user->image);
            }
            $request_data['image']=time().'_'.$image->hashname();
            Image::make($request->file('image'))
                ->resize(800, 800, function (Constraint $constraint) {
                    $constraint->aspectRatio();
                })
                ->save(storage_path('app/public/uploads/user_images/'). $request_data['image']);
        }
        $user->update($request_data);

        $user->roles()->detach();
        $user->permissions()->detach();

        if($request->roles != null){
            foreach ($request->roles as $role) {
                $user->roles()->attach($role);
                $user->save();
            }
        }
        if($request->permissions != null){
            foreach ($request->permissions as $permission) {
                $user->permissions()->attach($permission);
                $user->save();
            }
        }

        session()->flash('success', __('site.successfully.updated'));
        return redirect()->route('dashboard.users.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param User $user
     * @return RedirectResponse
     * @throws Exception
     */
    public function destroy(string $local, User $user): RedirectResponse
    {
        if ($user->image != 'default.png')
        {
            Storage::disk('public')->delete('uploads/user_images/'.$user->image);
        }
        $user->delete();
        session()->flash('success', __('site.successfully.deleted'));
        return redirect()->route('dashboard.users.index');
    }
}
