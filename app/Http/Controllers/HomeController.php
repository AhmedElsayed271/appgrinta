<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\FirebaseService;
use App\Traits\Notify;

class HomeController extends Controller
{
    use Notify;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth')->except('testNotification');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function index()
    {
        if (\Illuminate\Support\Facades\Auth::check())
        {
            return redirect()->route('dashboard.welcome');
        }
        return redirect()->route('login');
    }

    public function testNotification()
    {
        $tokens = ['ep0CRUSWRBOAAyuIOXxi7Q:APA91bGObYyDc5Pnp5MltoU5ZYE7Bc4hHpVmtPPmMS64mZNG-VMHHEo23VdKLTFkWxKKnViQj-UqL1Cr_A7JqL80qWjo1BRAiiWv3iszeZx8cLInqUPKbT0'];

        $this->topicNotifyByFirebaseTokens($tokens, [
            'title'  => 'Good Morning',
            'body'   => 'Body good morning',
            'image' => 'https://play-lh.googleusercontent.com/ob7h-akWXvMWGOJQo85AoBEp68eTeM5wu5lIw67vBd-QzqBV6gVtpWqw2c0ecCjtaw',
            'notify' => ['post_id' => '20'],
        ]);

        return response()->json(['status' => 'sent — check storage/logs/laravel.log for FCM response']);
    }
}
