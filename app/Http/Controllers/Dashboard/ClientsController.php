<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Level;
use App\Traits\CustomResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ClientsController extends Controller
{
    use CustomResponser;
    public function __construct()
    {
        $this->middleware('role:admin,client');
        $this->middleware('permission:view-client,full-permissions')->only('index');
        $this->middleware('permission:create-client,full-permissions')->only(['create','store']);
        $this->middleware('permission:update-client,full-permissions')->only(['edit','update']);
        $this->middleware('permission:delete-client,full-permissions')->only(['destroy']);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Http\JsonResponse
     */
    public function index()
    {
        if (\request()->ajax()){
            $query=request()->has('query')? request()->input('query'):[];
            $search=$query['generalSearch']??null;

            $items=$this->search(Client::query(),Client::SEARCHFIELDS,$search);
            return $this->showAll($items->get()->makeVisible(['action']));
        }
        $page_title = __('site.client.show');
        $page_description = __('site.client.page_description');
        return view('dashboard.clients.index',compact('page_title','page_description'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Http\Response
     */
    public function create()
    {
        $page_title = __('site.client.create');
//        $page_description = __('site.category.description');
        return \view('dashboard.clients.create',compact('page_title'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request, string $locale)
    {
        $rules=[
            'full_name'=>['required'],
            'email'=> ['required', 'string', 'email', 'max:255', 'unique:clients'],
            'password'=>['required','string', 'min:8', 'confirmed'],
            'timezone'=>['required','timezone'],
        ];
        $request->validate($rules);
        $request_data=$request->except(['password']);
        $request_data['password']=Hash::make($request->input('password'));
        Client::query()->create($request_data);
        session()->flash('success', __('site.successfully.added'));
        return redirect()->route('dashboard.clients.index');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(string $local, $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Client $client
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Http\Response
     */
    public function edit(string $local, Client $client)
    {
        $page_title = __('site.client.edit');
        return \view('dashboard.clients.edit',compact('page_title','client'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param Client $client
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, string $local, Client $client): \Illuminate\Http\RedirectResponse
    {
        $rules=[
            'full_name'=>['required'],
            'email'=> ['required', 'string', 'email', 'max:255', Rule::unique('clients','email')->ignore($client->id,'id')],
            'password' => ['string','nullable', 'min:8', 'confirmed'],
            'timezone'=>['required','timezone'],
        ];
        $request->validate($rules);
        $request_data=$request->except(['password']);
        if ($request->input('password')){
            $request_data['password']=Hash::make($request->input('password'));
        }
        $client->update($request_data);
        session()->flash('success', __('site.successfully.updated'));
        return redirect()->route('dashboard.clients.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Client $client
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(string $local, Client $client)
    {
        $client->delete();
        session()->flash('success', __('site.successfully.deleted'));
        return redirect()->back();
    }
}
