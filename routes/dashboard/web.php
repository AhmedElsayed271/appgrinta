<?php

use App\Http\Controllers\Api\V1\PostsController as ApiPostsController;
use App\Http\Controllers\Dashboard\CallApiController;
use App\Http\Controllers\Dashboard\CategoriesController;
use App\Http\Controllers\Dashboard\CkeditorController;
use App\Http\Controllers\Dashboard\ClientsController;
use App\Http\Controllers\Dashboard\CompetitionsController;
use App\Http\Controllers\Dashboard\CountriesController;
use App\Http\Controllers\Dashboard\MatchesController;
use App\Http\Controllers\Dashboard\MatchEventsController;
use App\Http\Controllers\Dashboard\NotificationsController;
use App\Http\Controllers\Dashboard\PlayersController;
use App\Http\Controllers\Dashboard\PostsController;
use App\Http\Controllers\Dashboard\RolesController;
use App\Http\Controllers\Dashboard\SettingsController;
use App\Http\Controllers\Dashboard\TeamsController;
use App\Http\Controllers\Dashboard\UsersController;
use App\Http\Controllers\Dashboard\WelcomeController;
use Illuminate\Support\Facades\Route;

Route::group(
    [
        'middleware' => ['localeSessionRedirect', 'localizationRedirect', 'localeViewPath']
    ],
    function () {
        Route::prefix('{locale}/dashboard')->name('dashboard.')
            ->middleware(['auth'])
            ->group(function () {

                // Welcome
                Route::get('/', [WelcomeController::class, 'index'])->name('welcome');

                // Users
                Route::resource('users', UsersController::class);

                // Roles
                Route::resource('roles', RolesController::class);

                // Notifications
                Route::prefix('notifications')->name('notifications.')->group(function () {
                    Route::get('post',              [NotificationsController::class, 'post'])->name('post');
                    Route::post('post_post',        [NotificationsController::class, 'post_post'])->name('post_post');
                    Route::get('match',             [NotificationsController::class, 'match'])->name('match');
                    Route::post('match_post',       [NotificationsController::class, 'match_post'])->name('match_post');
                    Route::get('url',               [NotificationsController::class, 'url'])->name('url');
                    Route::post('url_post',         [NotificationsController::class, 'url_post'])->name('url_post');
                    Route::get('team',              [NotificationsController::class, 'team'])->name('team');
                    Route::post('team_post',        [NotificationsController::class, 'team_post'])->name('team_post');
                    Route::get('competition',       [NotificationsController::class, 'competition'])->name('competition');
                    Route::post('competition_post', [NotificationsController::class, 'competition_post'])->name('competition_post');
                });

                // Categories
                Route::resource('categories', CategoriesController::class);

                // Posts
                Route::resource('posts', PostsController::class);
                Route::get('post/team/{team}',      [PostsController::class, 'getTeam'])->name('post.team');
                Route::post('destroyAll',           [PostsController::class, 'destroyAll'])->name('posts.destroyAll');
                Route::post('featureAll',           [PostsController::class, 'featureAll'])->name('posts.featureAll');
                Route::post('unfeatureAll',         [PostsController::class, 'unfeatureAll'])->name('posts.unfeatureAll');
                Route::post('assign-all',           [PostsController::class, 'assignAll'])->name('posts.assignAll');
                Route::post('changePostSort',       [PostsController::class, 'changePostSort'])->name('posts.changePostSort');

                // Countries
                Route::resource('countries', CountriesController::class);

                // Competitions
                Route::resource('competitions', CompetitionsController::class);
                Route::get('/competition/createfootballApi',    [CompetitionsController::class, 'createCompetitionFootballApi'])->name('createCompetitionFootballApi');
                Route::post('/competition/storefootballApi',    [CompetitionsController::class, 'storeCompetitionFootballApi'])->name('storeCompetitionFootballApi');
                Route::get('competitions/{competition}/points', [CompetitionsController::class, 'points'])->name('competitions.points');
                Route::get('competitions/{competition}/loadTeams', [CompetitionsController::class, 'loadTeams'])->name('competitions.loadTeams');
                Route::post('changeSort',                       [CompetitionsController::class, 'changeSort'])->name('competitions.changeSort');
                Route::post('changeCompetitionSort',            [CompetitionsController::class, 'changeCompetitionSort'])->name('competitions.changeCompetitionSort');

                // Teams
                Route::resource('teams', TeamsController::class);
                Route::get('teams/{team}/loadPlayers', [TeamsController::class, 'loadPlayers'])->name('teams.loadPlayers');

                // Clients
                Route::resource('clients', ClientsController::class);

                // Players
                Route::resource('players', PlayersController::class);

                // Matches
                Route::resource('matches', MatchesController::class);
                Route::resource('matches.events', MatchEventsController::class);

                // Ckeditor
                Route::post('ckeditor/image_upload', [CkeditorController::class, 'upload'])->name('ckeditor.upload');
                Route::post('ckeditor/image_delete', [CkeditorController::class, 'delete'])->name('ckeditor.delete');

                // Settings
                Route::resource('settings', SettingsController::class)->only(['index', 'update']);
                Route::post('updateLeaguesAndSeason', [SettingsController::class, 'updateLeaguesAndSeason'])->name('updateLeaguesAndSeason');

                // Call API
                Route::get('call_api',              [CallApiController::class, 'index']);
                Route::get('call_api_team',         [CallApiController::class, 'add_football_team_id']);
                Route::get('call_api_team_league',  [CallApiController::class, 'teamsWithLeague']);
                Route::get('call_api_player_team',  [CallApiController::class, 'playerWithTeams']);

                // Post Data
                Route::post('updatePostData', [ApiPostsController::class, 'updatePostData'])->name('updatePostData');

                // CURRENT - becomes dashboard.updateInMinute
                Route::get('settings/update-in-minute/{minute}', [
                    App\Http\Controllers\GetDataFromFootBallApi::class, 'updateInMinutes'
                ])->name('updateInMinute');
                
                Route::get('settings/start-update', [
                    App\Http\Controllers\GetDataFromFootBallApi::class, 'startUpdateMatchesEvents'
                ])->name('startUpdate');
                
                Route::get('settings/stop-update', [
                    App\Http\Controllers\GetDataFromFootBallApi::class, 'stopUpdateMatchesEvents'
                ])->name('stopUpdate');
            });
    }
);
