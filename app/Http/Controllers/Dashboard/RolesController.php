<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use App\Models\Role;
use App\Models\Permission;
use App\Traits\CustomResponser;

class RolesController extends Controller
{
    use  CustomResponser;
    public function __construct()
    {
        $this->middleware('role:admin,role');
        $this->middleware('permission:view-role,full-permissions')->only('index');
        $this->middleware('permission:create-role,full-permissions')->only(['create','store']);
        $this->middleware('permission:update-role,full-permissions')->only(['edit','update']);
        $this->middleware('permission:delete-role,full-permissions')->only(['destroy']);
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
            $items=$this->search(Role::query(),Role::SEARCHFIELDS,$search);
            return $this->showAll($items->get()->load('permissions'));
        }
        $page_title = __('site.role.show');
        $page_description = __('site.role.page_description');
        return view('dashboard.roles.index',compact('page_title','page_description'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Application|Factory|View|Response
     */
    public function create()
    {
        $page_title = __('site.role.create');
//        $page_description = __('site.category.description');
        return \view('dashboard.roles.create',compact('page_title'));
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
            'name' => ['required','max:255',Rule::unique('roles','name')],
            'slug' => ['required','max:255',Rule::unique('roles','name')],
        ];
        $request->validate($rules);

        $role = new Role();
        $role->name = $request->name;
        $role->slug = $request->slug;
        $role-> save();

        $permissions=[];
        foreach(json_decode($request->input('permissions')) as $permission)
        {
            $permissions[]=$permission->value;
        }

        foreach ($permissions as $permission) {
            $permissions = new Permission();
            $permissions->name = $permission;
            $permissions->slug = strtolower(str_replace(" ", "-", $permission));
            $permissions->save();
            $role->permissions()->attach($permissions->id);
            $role->save();
        }
        session()->flash('success', __('site.successfully.added'));
        return redirect()->route('dashboard.roles.index');
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
     * @param Role $role
     * @return Application|Factory|View|Response
     */
    public function edit(string $local, Role $role)
    {
        $permissions=[];
        foreach($role->permissions as $permission)
        {
            $permissions[]=$permission->name;
        }
        $form_permission=implode ( ',' ,  $permissions);
        $page_title = __('site.role.edit');
        return \view('dashboard.roles.edit',compact('role','form_permission','page_title'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param Role $role
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, string $local, Role $role): \Illuminate\Http\RedirectResponse
    {
        $rules=[
            'name' => ['required','max:255',Rule::unique('roles','name')->ignore($role->id)],
            'slug' => ['required','max:255',Rule::unique('roles','name')->ignore($role->id)],
        ];
        $request->validate($rules);

        $role->name = $request->name;
        $role->slug = $request->slug;
        $role->save();

        $role->permissions()->delete();
        $role->permissions()->detach();

        $permissions=[];
        foreach(json_decode($request->input('permissions')) as $permission)
        {
            $permissions[]=$permission->value;
        }

        foreach ($permissions as $permission) {
            $permissions = new Permission();
            $permissions->name = $permission;
            $permissions->slug = strtolower(str_replace(" ", "-", $permission));
            $permissions->save();
            $role->permissions()->attach($permissions->id);
            $role->save();
        }

        session()->flash('success', __('site.successfully.updated'));
        return redirect()->route('dashboard.roles.index');
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
}
