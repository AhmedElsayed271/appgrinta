<?php

use App\Http\Controllers\Api\V1\CategoriesController;
use App\Models\Competition;
use Illuminate\Support\Facades\Route;
use App\Traits\NotificationOfMatchesTrait;
use Illuminate\Support\Facades\DB;
use App\Models\Matche;
use Carbon\Carbon;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
//use App\Mail\TestEmail; // إذا كنت تستخدم Mailable.

// Redirect plain root to localized root (locale prefix will be added)
Route::get('/', function () {
    return redirect(LaravelLocalization::localizeURL('/'));
});

Route::group([
    'prefix' => LaravelLocalization::setLocale(),
    'middleware' => ['localeSessionRedirect', 'localizationRedirect', 'localeViewPath'],
], function () {

    Route::get('/send-test-email', function () {
        // هنا يمكنك إرسال البريد مباشرة باستخدام Mail::raw أو Mailable.

        // إرسال بريد باستخدام Mail::raw
        Mail::raw('This is a test email from Laravel.', function ($message) {
            $message->to('mydevpro1@gmail.com')
                    ->subject('Test Email');
        });

        return 'Email sent!';
    });

    Route::get('/cache-all', function () {
        Artisan::call('config:cache');
        Artisan::call('route:cache');
        Artisan::call('view:cache');

        return "All caches have been cleared and regenerated!";
    });

    Route::get('/test-matches', function() {

        $matches=Matche::query()->whereNotNull('fixture_id')
            ->where('match_date', '<',Carbon::now('Africa/Cairo')
            ->addMinutes(45)->setSeconds(0)
            ->format('Y-m-d H:i:s'))
            ->where('match_date', '>', Carbon::now('Africa/Cairo')
            ->subMinutes(45)->setSeconds(0)
            ->format('Y-m-d H:i:s'))
            ->whereNotIn('status', ['PST','CANC','SUSP','ABD', 'AWD', 'WO'])->with('competition.parent')
            ->get()->each(function ($builder){
                $builder->home_team_name_ar = $builder->team1()->first()->translate('ar')->name;
                $builder->home_team_name_en = $builder->team1()->first()->translate('en')->name;
                $builder->away_team_name_ar = $builder->team2()->first()->translate('ar')->name;
                $builder->away_team_name_en = $builder->team2()->first()->translate('en')->name;
                // $builder->home_goals=$builder->events()->getQuery()->where('status','goal')->where('team_id',$builder->team1_id)->get()->count();
                // $builder->away_goals=$builder->events()->getQuery()->where('status','goal')->where('team_id',$builder->team2_id)->get()->count();
                $builder->home_image = $builder->team1()->first()->image_path;
                $builder->away_image = $builder->team2()->first()->image_path;
            });

        return $matches;
    });

    // Localized root (for /{locale}/) and authentication routes
    Route::get('/', function () {
        if (\Illuminate\Support\Facades\Auth::check())
        {
            return redirect()->route('dashboard.welcome');
        }
        return redirect()->route('login');
    });

    Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

    // Login
    Route::get('/login', [App\Http\Controllers\Auth\LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [App\Http\Controllers\Auth\LoginController::class, 'login']);
    Route::post('/logout', [App\Http\Controllers\Auth\LoginController::class, 'logout'])->name('logout');

    // Register (remove if not needed)
    Route::get('/register', [App\Http\Controllers\Auth\RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [App\Http\Controllers\Auth\RegisterController::class, 'register']);

    // Password reset (remove if not needed)
    Route::get('/password/reset', [App\Http\Controllers\Auth\ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/password/email', [App\Http\Controllers\Auth\ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');

    // Route::get('/createlink', function(){
    //     \Artisan::call("storage:link") ;
    // });

    Route::get('time_now',function (){
        return \Carbon\Carbon::now('Africa/Cairo')->toDateTimeString();
    });

    Route::get('termsAndConditions',function (){
        return view('termsAndConditions');
    });

    Route::get('privacyPolicy',function (){
        return view('privacyPolicy');
    });

    Route::get('test-notification', [App\Http\Controllers\HomeController::class, 'testNotification']);

});
