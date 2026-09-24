<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Mail\VerifiedEmail;
use App\Models\Client;
use App\Traits\ApiResponser;
use App\Traits\Helper;
use Carbon\Carbon;
use Exception;
use Google\Client as GoogleClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    use ApiResponser,Helper;
    public function __construct()
    {
        $this->middleware('auth:api')->only(['logout','verifiedEmail','deleteAccount']);
    }
    public function login (Request $request): \Illuminate\Http\JsonResponse
    {
        $rules= [
            'email' => 'required|string|max:255',
            'password' => 'required|string|min:6',
            'fb_token'=>['required'],
            'locale'=>['required']
        ];
        $request->validate($rules);
        if($request->input('locale') != "en" && $request->input('locale') != "ar"){
            $request->locale = "en";
        }
        $client = Client::query()->where('email', $request->email)->first();
        if (!$client || !Hash::check($request->password, $client->password)) {
            return $this->errorResponse('User does not exist', 400);
        }
        if (Hash::check($request->input('password'), $client->password)) {
            $token = now() .Str::random(60);
            $client->update(['api_token'=>$token,'fb_token'=>$request->input('fb_token'),'locale'=>$request->input('locale')]);
            $response = ['token' => $token,'client'=>$client];
            return $this->successResponse($response, 200);
        } else {
            return $this->errorResponse("Password mismatch", 400);
        }
    }

    public function register(Request $request): \Illuminate\Http\JsonResponse
    {   

        $rules=[
            'full_name'=>['required'],
            'email'=> ['required', 'string', 'email', 'max:255', 'unique:clients'],
            'password'=>['required','string', 'min:8', 'confirmed'],
            'fb_token'=>['required'],
            'locale'=>['required']
        ];
        $request->validate($rules);
        if($request->input('locale') != "en" && $request->input('locale') != "ar"){
            $request->locale = "en";
        }
        $request_data=$request->except(['password']);
        $request_data['password']=Hash::make($request->input('password'));
        $request_data['fb_token']=$request->input('fb_token');
        $request_data['locale']=$request->input('locale');
        $request_data['email_verified_at'] = now();
        $request_data['verified_code']=$this->generateRandomString(4);
        $client=Client::query()->create($request_data);
        if ($request->input('email')){
            Mail::to($request->input('email'))->send(new VerifiedEmail($request_data['verified_code'],$client->name, $client->locale));
        }
        $token = now() . Str::random(60);
        $client->update(['api_token'=>$token]);
        $response = ['token' => $token,'client'=>$client];
        return $this->successResponse($response, 200);
    }

    public  function social(Request $request): JsonResponse
    {
        $rules=[
//            'full_name'=>['required'],
//            'email'=> ['required', 'string', 'email', 'max:255'],
            'fb_token'=>['required'],
            'social_id'=>['required'],
            'locale'=>['required']
        ];
        $request->validate($rules);
        if($request->input('locale') != "en" && $request->input('locale') != "ar"){
            $request->locale = "en";
        }
        $socialId = $request->input('social_id');
        $email    = $request->input('email');

        $is_already_registered = false;

        // 1) Already registered with this social_id -> just log in
        $client = Client::query()->where('social_id', $socialId)->first();
        if (!is_null($client)) {
            $is_already_registered = true;
        }

        // 2) Social login found -> link social_id to existing password account
        if ($client === null && $email !== null) {
            $clientWithEmail = Client::query()->where('email', $email)->first();
            if ($clientWithEmail !== null) {
                if ($clientWithEmail->social_id !== null && $clientWithEmail->social_id !== $socialId) {
                    return $this->errorResponse('This email is already linked to another social account', 400);
                }
                $client = $clientWithEmail;
                $client->update([
                    'social_id'         => $socialId,
                    'email_verified_at' => $clientWithEmail->email_verified_at ?? now(),
                ]);
            }
        }

        // 3) No existing account at all -> create a new one (never without email)
        if ($client === null) {
            if ($email === null) {
                return $this->errorResponse('email is required to create an account', 400);
            }
            $request_data = $request->except(['password']);
            $request_data['verified_code']     = null;
            $request_data['email_verified_at'] = now();
            $request_data['password']          = Hash::make(Str::random(40));
            $client = Client::query()->create($request_data);
        }

        $token = now() . Str::random(60);
        $client->update(['api_token'=>$token,'locale'=>$request->input('locale'),'fb_token'=>$request->input('fb_token')]);
        $response = ['token' => $token,'registered_client' => $is_already_registered, 'client'=>$client];
        return $this->successResponse($response, 200);
    }

    public function google(Request $request): JsonResponse
    {
        $rules = [
            'id_token' => ['required'],
            'fb_token' => ['required'],
            'locale'   => ['required'],
        ];
        $request->validate($rules);

        if (!in_array($request->input('locale'), ['en', 'ar'])) {
            $request->locale = 'en';
        }

        try {
            $client = new GoogleClient(['client_id' => config('services.google.client_id')]);
            $payload = $client->verifyIdToken($request->input('id_token'));
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }

        if (!$payload) {
            return $this->errorResponse('Invalid Google token', 400);
        }

        $socialId   = $payload['sub'];
        $email      = $payload['email'] ?? $request->input('email');
        $fullName   = $payload['name'] ?? $request->input('full_name');

        // 1) Already registered with this social_id -> just log in
        $client = Client::query()->where('social_id', $socialId)->first();
        $isAlreadyRegistered = !is_null($client);

        // 2) Same email has a password account -> link social_id to it
        if ($client === null && $email !== null) {
            $clientWithEmail = Client::query()->where('email', $email)->first();
            if ($clientWithEmail !== null) {
                if ($clientWithEmail->social_id !== null && $clientWithEmail->social_id !== $socialId) {
                    return $this->errorResponse('This email is already linked to another Google account', 400);
                }
                $client = $clientWithEmail;
                $client->update([
                    'social_id'         => $socialId,
                    'email_verified_at' => $clientWithEmail->email_verified_at ?? now(),
                ]);
            }
        }

        // 3) No existing account at all -> create a new one (never without email)
        if ($client === null) {
            if ($email === null) {
                return $this->errorResponse('A verified Google email is required', 400);
            }
            $client = Client::query()->create([
                'full_name'        => $fullName,
                'email'            => $email,
                'password'         => Hash::make(Str::random(40)),
                'social_id'        => $socialId,
                'fb_token'         => $request->input('fb_token'),
                'verified_code'    => null,
                'email_verified_at'=> now(),
                'locale'           => $request->input('locale'),
            ]);
        }

        $token = now() . Str::random(60);
        $client->update([
            'api_token' => $token,
            'locale'    => $request->input('locale'),
            'fb_token'  => $request->input('fb_token'),
        ]);

        $response = [
            'token'             => $token,
            'registered_client' => $isAlreadyRegistered,
            'client'            => $client,
        ];

        return $this->successResponse($response, 200);
    }

    public function logout(): \Illuminate\Http\JsonResponse
    {
        $client=Client::findOrFail(auth()->user()->id);
        $client->update([
            'api_token'=>null,
            'fb_token'=>null,
        ]);
        return response()->json([
            'code'=>200,
            'message'=>'You logout successfully'
        ]);
    }
    public function deleteAccount(): \Illuminate\Http\JsonResponse
    {
        $client=Client::findOrFail(auth()->user()->id);
        $client->delete();
        return response()->json([
            'code'=>200,
            'message'=>'You delete you account successfully'
        ]);
    }

    public function verifiedEmail(Request $request): JsonResponse
    {
        $rules=[
            'verified_code'=>'required'
        ];
        $request->validate($rules);
        if (auth()->user()->verified_code != $request->input('verified_code')){
            return $this->errorResponse('Mismatch verified code',400);
        }
        auth()->user()->update([
            'verified_code'=>null,
            'email_verified_at'=>Carbon::now()
        ]);
        return $this->successResponse([
            'message'=>'the account is verified',
            'code'=>200
        ],200);
    }
}
