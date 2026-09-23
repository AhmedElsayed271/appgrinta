<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CategoriesController;
use App\Http\Controllers\Api\V1\ClientController;
use App\Http\Controllers\Api\V1\CompetitionsController;
use App\Http\Controllers\Api\V1\CountriesController;
use App\Http\Controllers\Api\V1\GuestController;
use App\Http\Controllers\Api\V1\MatchesController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\PlayersController;
use App\Http\Controllers\Api\V1\PostsController;
use App\Http\Controllers\Api\V1\SettingController;
use App\Http\Controllers\Api\V1\TeamsController;
use App\Http\Controllers\GetDataFromFootBallApi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

// Auth Routes
Route::post('login',        [AuthController::class, 'login']);
// Route::post('guest',        [GuestController::class, 'storeGuest']);
Route::post('social',       [AuthController::class, 'social']);
Route::post('register',     [AuthController::class, 'register']);
Route::post('logout',       [AuthController::class, 'logout'])->middleware('auth:api');
Route::delete('delete-account', [AuthController::class, 'deleteAccount'])->middleware('auth:api');
Route::post('verify_email', [AuthController::class, 'verifiedEmail']);

Route::middleware('auth:api')->group(function() {

    // Categories
    Route::get('categories',            [CategoriesController::class, 'index']);
    Route::get('categories/{category}', [CategoriesController::class, 'show']);

    // Posts
    Route::get('posts',         [PostsController::class, 'index']);
    Route::get('posts/all',     [PostsController::class, 'allposts']);
    Route::get('posts/{post}',  [PostsController::class, 'show']);
    Route::get('posts-all',     [PostsController::class, 'sendPosts']);
    Route::get('featuredPosts', [PostsController::class, 'featuredPosts']);
    Route::get('allPosts',      [PostsController::class, 'allposts']);
    Route::get('search/posts',  [PostsController::class, 'searchPosts']);

    // Competitions
    Route::get('competitions',                          [CompetitionsController::class, 'index']);
    Route::get('competitions/{id}',                     [CompetitionsController::class, 'show']);
    Route::get('competitions/{id}/posts',               [CompetitionsController::class, 'posts']);
    Route::get('competitions/{id}/postsd',              [CompetitionsController::class, 'postsd']);
    Route::get('competitions/{id}/teams',               [CompetitionsController::class, 'teams']);
    Route::get('competitions/{id}/teamsdash',           [CompetitionsController::class, 'teamsdash']);
    Route::get('competitions/{id}/points',              [CompetitionsController::class, 'points']);
    Route::get('competitions/{id}/matches',             [CompetitionsController::class, 'matches']);
    Route::get('competitions/{competition}/child',      [CompetitionsController::class, 'child']);

    // Teams
    Route::get('teams',                                             [TeamsController::class, 'index']);
    Route::post('in/teams',                                         [TeamsController::class, 'getTeam']);
    Route::get('search/teams',                                      [TeamsController::class, 'searchTeams']);
    Route::get('teams/{id}',                                        [TeamsController::class, 'show']);
    Route::get('teams/{id}/teamd',                                  [TeamsController::class, 'teamd']);
    Route::get('teams/{id}/posts',                                  [TeamsController::class, 'posts']);
    Route::get('teams/{id}/competitions',                           [TeamsController::class, 'competitions']);
    Route::get('teams/{id}/matches',                                [TeamsController::class, 'matches']);
    Route::get('teams/{team_id}/standing/{competition_id}',         [TeamsController::class, 'standing']);
    Route::get('teams/{team_id}/standing_competitions',             [TeamsController::class, 'standingCompetition']);
    Route::get('teams/{team1_id}/confrontations/{team2_id}',        [TeamsController::class, 'teamMatches']);

    // Players
    Route::get('players',               [PlayersController::class, 'index']);
    Route::post('players/allpl',        [PlayersController::class, 'allpl']);
    Route::get('players/{id}',          [PlayersController::class, 'show']);
    Route::get('players/{id}/statistic',[PlayersController::class, 'statistic']);

    // Matches
    Route::get('matches',               [MatchesController::class, 'index']);
    Route::get('matches/{match}',       [MatchesController::class, 'show']);
    Route::get('matches/{match}/events',[MatchesController::class, 'events']);

    // Client
    Route::get('client/profile',                    [ClientController::class, 'profile']);
    Route::post('client/profile/update',            [ClientController::class, 'updateProfile']);
    Route::post('client/profile/updatelocale',      [ClientController::class, 'updatelocale']);
    Route::get('client/favourite_teams',            [ClientController::class, 'favouriteTeams']);
    Route::post('client/favourite_teams',           [ClientController::class, 'addToFavouriteTeam']);
    Route::delete('client/favourite_teams',         [ClientController::class, 'removeFromFavouriteTeam']);
    Route::get('client/favourite_competitions',     [ClientController::class, 'favouriteCompetitions']);
    Route::post('client/favourite_competitions',    [ClientController::class, 'addToFavouriteCompetition']);
    Route::delete('client/favourite_competitions',  [ClientController::class, 'removeFromFavouriteCompetition']);

});

// Countries
Route::get('countries',             [CountriesController::class, 'index']);
Route::get('countries/{country}',   [CountriesController::class, 'show']);

// Notifications
Route::get('notification/daily/{id}',   [NotificationController::class, 'daily']);
Route::get('notification/weekly/{id}',  [NotificationController::class, 'weekly']);

// Settings
Route::get('setting', [SettingController::class, 'setting']);

// Password Reset
Route::post('sent_token_to_email',  [ClientController::class, 'getToken']);
Route::post('change_password',      [ClientController::class, 'changePassword']);
