<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ClientResource;
use App\Http\Resources\CompetitionResource;
use App\Http\Resources\TeamResource;
use App\Mail\SendTokenToEmail;
use App\Models\Client;
use App\Traits\ApiResponser;
use App\Traits\Helper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class ClientController extends Controller
{
    use ApiResponser,Helper;
    public function __construct()
    {
        $this->middleware('auth:api')->except(['getToken','changePassword']);
    }

    public function profile(): \Illuminate\Http\JsonResponse
    {
        $client=Client::query()->findOrFail(auth()->user()->getAuthIdentifier());
        return $this->successResponse( new ClientResource($client),200);
    }
    public function updateProfile(Request $request): \Illuminate\Http\JsonResponse
    {
        $rules=[
            'full_name'=>['required'],
            'email'=> ['required', 'string', 'email', 'max:255', Rule::unique('clients','email')->ignore(auth()->user()->getAuthIdentifier(),'id')],
            'password' => ['string','nullable', 'min:8'],
            'timezone' => ['nullable','timezone'],
        ];
        $request->validate($rules);
        $client=Client::query()->findOrFail(auth()->user()->getAuthIdentifier());
        $request_data=$request->except(['password']);
        if($request->input('password'))
        {
            $request_data['password']=Hash::make($request->input('password'));
        }
        $client->update($request_data);
        return $this->successResponse(new ClientResource($client),200);
    }
    public function updatelocale(Request $request): \Illuminate\Http\JsonResponse
    {
        $rules=[
            'locale'=>['required',Rule::in(['ar', 'en'])],
        ];
        $request->validate($rules);
        $client=Client::query()->findOrFail(auth()->user()->getAuthIdentifier());
        $request_data=$request->except(['password']);
        if($request->input('password'))
        {
            $request_data['password']=Hash::make($request->input('password'));
        }
        $client->update(['locale'=>$request->input('locale')]);
        return $this->successResponse(new ClientResource($client),200);
    }

    public function getToken(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'email' => ['required','string','email','exists:clients,email'],
        ]);
        $client=Client::query()->where('email',$request->input('email'))->first();
        $token=$this->generateRandomString(7);
        $password_reset = DB::table('password_resets')->where('email',$request->input('email'))->first();
        if ($password_reset)
        {
            $token=$password_reset->token;
        }
        else{
            DB::table('password_resets')->insert([
                'email'=>$request->input('email'),
                'token'=>$token
            ]);
        }
        Mail::to($request->input('email'))->send(new SendTokenToEmail($token,$client->full_name));
        return $this->successResponse('Code is sent successfully to '.$request->input('email'),200);
    }

    public function changePassword(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'password' => 'required|string|min:6|confirmed',
            'token'=>['required','exists:password_resets,token']
        ]);
        $password_reset = DB::table('password_resets')->where('token',$request->input('token'))->first();
        $client=Client::query()->where('email',$password_reset->email)->first();
        $client->password=Hash::make($request->input('password'));
        $client->save();
        DB::table('password_resets')->where('token',$request->input('token'))->delete();
        return $this->successResponse('Password Has changed successfully',200);
    }


    public function favouriteTeams(): \Illuminate\Http\JsonResponse
    {
        $client=Client::query()->findOrFail(auth()->user()->getAuthIdentifier());
        return $this->showAll(TeamResource::collection($client->favouriteTeams()->get())->collection);
    }
    public function addToFavouriteTeam(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate(['team_id'=>['exists:teams,id']]);
        $client=Client::query()->findOrFail(auth()->user()->getAuthIdentifier());
        $client->favouriteTeams()->syncWithoutDetaching($request->input('team_id'));
        return $this->showAll(TeamResource::collection($client->favouriteTeams()->get())->collection);
    }
    public function removeFromFavouriteTeam(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate(['team_id'=>['exists:teams,id']]);
        $client=Client::query()->findOrFail(auth()->user()->getAuthIdentifier());
        $client->favouriteTeams()->detach($request->input('team_id'));
        return $this->showAll(TeamResource::collection($client->favouriteTeams()->get())->collection);
    }
    public function favouriteCompetitions(): \Illuminate\Http\JsonResponse
    {
        $client=Client::query()->findOrFail(auth()->user()->getAuthIdentifier());
        return $this->showAll(CompetitionResource::collection($client->favouriteCompetitions()->get())->collection);
    }
    public function addToFavouriteCompetition(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate(['competition_id'=>['exists:competitions,id']]);
        $client=Client::query()->findOrFail(auth()->user()->getAuthIdentifier());
        $client->favouriteCompetitions()->syncWithoutDetaching($request->input('competition_id'));
        return $this->showAll(CompetitionResource::collection($client->favouriteCompetitions()->get())->collection);
    }
    public function removeFromFavouriteCompetition(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate(['competition_id'=>['exists:competitions,id']]);
        $client=Client::query()->findOrFail(auth()->user()->getAuthIdentifier());
        $client->favouriteCompetitions()->detach($request->input('competition_id'));
        return $this->showAll(CompetitionResource::collection($client->favouriteCompetitions()->get())->collection);
    }
}
