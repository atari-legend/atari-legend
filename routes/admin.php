<?php

use App\Http\Controllers\Admin\Ajax\SNDHController;
use App\Http\Controllers\Admin\Ajax\UserController as AjaxUserController;
use App\Http\Controllers\Admin\Games\GameCompanyController;
use App\Http\Controllers\Admin\Games\GameConfigurationController;
use App\Http\Controllers\Admin\Games\GameController;
use App\Http\Controllers\Admin\Games\GameCreditsController;
use App\Http\Controllers\Admin\Games\GameIndividualController;
use App\Http\Controllers\Admin\Games\GameMusicController;
use App\Http\Controllers\Admin\Games\GameScreenshotsController;
use App\Http\Controllers\Admin\Games\GameSubmissionController;
use App\Http\Controllers\Admin\Games\GameVideoController;
use App\Http\Controllers\Admin\Games\IssuesController;
use App\Http\Controllers\Admin\Games\MusicController;
use App\Http\Controllers\Admin\HomeController as AdminHomeController;
use App\Http\Controllers\Admin\Menus\MenuConditionsController;
use App\Http\Controllers\Admin\Menus\MenuCrewController;
use App\Http\Controllers\Admin\Menus\MenuDisksContentController;
use App\Http\Controllers\Admin\Menus\MenuDisksController;
use App\Http\Controllers\Admin\Menus\MenusController;
use App\Http\Controllers\Admin\Menus\MenuSetsController;
use App\Http\Controllers\Admin\Menus\MenuSoftwareContentTypesController;
use App\Http\Controllers\Admin\Menus\MenuSoftwareController;
use App\Http\Controllers\Admin\News\NewsController;
use App\Http\Controllers\Admin\News\NewsSubmissionsController;
use App\Http\Controllers\Admin\Other\QuoteController;
use App\Http\Controllers\Admin\Other\TriviaController;
use App\Http\Controllers\Admin\User\CommentController;
use App\Http\Controllers\Admin\User\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('verified')->group(function () {
    Route::middleware('admin')->group(function () {
        Route::name('admin.')->group(function () {
            Route::prefix('/admin')->group(function () {
                Route::get('/', [AdminHomeController::class, 'index'])->name('home.index');

                Route::prefix('/games')->name('games.')->group(function () {
                    Route::get('issues', [IssuesController::class, 'index'])->name('issues');
                    Route::post('issues/genres/{game}', [IssuesController::class, 'setGenres'])->name('issues.genres');

                    Route::get('music', [MusicController::class, 'index'])->name('music');
                    Route::post('music', [MusicController::class, 'associate'])->name('music.associate');

                    Route::get('games/{game}/music', [GameMusicController::class, 'index'])->name('game-music.index');
                    Route::post('games/{game}/music', [GameMusicController::class, 'store'])->name('game-music.store');
                    Route::post('games/{game}/music/associate', [GameMusicController::class, 'associate'])->name('game-music.associate');
                    Route::delete('games/{game}/music/{sndh}', [GameMusicController::class, 'destroy'])
                        ->where(['sndh' => '[\w\s\-_\/()]+'])
                        ->name('game-music.destroy');

                    Route::get('games/{game}/credits', [GameCreditsController::class, 'index'])->name('game-credits.index');
                    Route::post('games/{game}/credits', [GameCreditsController::class, 'store'])->name('game-credits.store');
                    Route::delete('games/{game}/credits/{individual}', [GameCreditsController::class, 'destroy'])->name('game-credits.destroy');

                    Route::get('games/{game}/screenshots', [GameScreenshotsController::class, 'index'])->name('game-screenshots.index');
                    Route::post('games/{game}/screenshots', [GameScreenshotsController::class, 'store'])->name('game-screenshots.store');
                    Route::delete('games/{game}/screenshots/{screenshot}', [GameScreenshotsController::class, 'destroy'])->name('game-screenshots.destroy');

                    Route::get('games/{game}/videos', [GameVideoController::class, 'index'])->name('game-videos.index');
                    Route::post('games/{game}/videos', [GameVideoController::class, 'store'])->name('game-videos.store');
                    Route::delete('games/{game}/videos/{video}', [GameVideoController::class, 'destroy'])->name('game-videos.destroy');

                    Route::resource('games', GameController::class);

                    Route::resource('submissions', GameSubmissionController::class);
                    Route::delete('submissions/{submission}/screenshots/{screenshot}', [GameSubmissionController::class, 'destroyScreenshot'])->name('submissions.screenshots.destroy');

                    Route::delete('individuals/{individual}/avatar', [GameIndividualController::class, 'destroyAvatar'])->name('individuals.avatar.destroy');
                    Route::post('individuals/{individual}/nickname', [GameIndividualController::class, 'storeNickname'])->name('individuals.nickname.store');
                    Route::delete('individuals/{individual}/nickname/{nickname}', [GameIndividualController::class, 'destroyNickname'])->name('individuals.nickname.destroy');
                    Route::resource('individuals', GameIndividualController::class);

                    Route::delete('companies/{company}/logo', [GameCompanyController::class, 'destroyLogo'])->name('companies.logo.destroy');
                    Route::resource('companies', GameCompanyController::class);

                    Route::get('config', function () {
                        return redirect()->route('admin.games.configuration.show', 'engine');
                    })->name('configuration.index');
                    Route::get('config/{type}', [GameConfigurationController::class, 'index'])->name('configuration.show');
                    Route::post('config/{type}', [GameConfigurationController::class, 'store'])->name('configuration.store');
                    Route::put('config/{type}/{id}', [GameConfigurationController::class, 'update'])->name('configuration.update');
                    Route::delete('config/{type}/{id}', [GameConfigurationController::class, 'destroy'])->name('configuration.destroy');
                });

                Route::prefix('/users')->name('users.')->group(function () {
                    Route::delete('users/{user}/avatar', [UserController::class, 'destroyAvatar'])->name('users.avatar');
                    Route::resource('users', UserController::class);
                    Route::resource('comments', CommentController::class);
                });

                Route::prefix('/news')->name('news.')->group(function () {
                    Route::delete('news/{news}/image', [NewsController::class, 'destroyImage'])->name('news.image');
                    Route::resource('news', NewsController::class);

                    Route::get('submissions', [NewsSubmissionsController::class, 'index'])->name('submissions.index');
                    Route::delete('submissions/{submission}', [NewsSubmissionsController::class, 'destroy'])->name('submissions.destroy');
                    Route::post('submissions/{submission}', [NewsSubmissionsController::class, 'approve'])->name('submissions.approve');
                });

                Route::prefix('/others')->name('others.')->group(function () {
                    Route::resource('trivias', TriviaController::class);
                    Route::resource('quotes', QuoteController::class);
                });

                Route::prefix('/menus')->name('menus.')->group(function () {
                    Route::resource('sets', MenuSetsController::class);

                    Route::resource('menus', MenusController::class);

                    Route::resource('disks', MenuDisksController::class);
                    Route::post('/disks/{disk}/screenshot', [MenuDisksController::class, 'storeScreenshot'])->name('disks.storeScreenshot');
                    Route::delete('/disks/{disk}/screenshot/{screenshot}', [MenuDisksController::class, 'destroyScreenshot'])->name('disks.destroyScreenshot');
                    Route::post('/disks/{disk}/dump', [MenuDisksController::class, 'storeDump'])->name('disks.storeDump');
                    Route::delete('/disks/{disk}/dump/{dump}', [MenuDisksController::class, 'destroyDump'])->name('disks.destroyDump');

                    Route::resource('disks.content', MenuDisksContentController::class);

                    Route::resource('conditions', MenuConditionsController::class);

                    Route::resource('content-types', MenuSoftwareContentTypesController::class);

                    Route::resource('software', MenuSoftwareController::class);

                    Route::resource('crews', MenuCrewController::class);
                    Route::post('/crews/{crew}/individual', [MenuCrewController::class, 'addIndividual'])->name('crews.addIndividual');
                    Route::delete('/crews/{crew}/individual/{individual}', [MenuCrewController::class, 'removeIndividual'])->name('crews.removeIndividual');
                    Route::post('/crews/{crew}/subcrew', [MenuCrewController::class, 'addSubCrew'])->name('crews.addSubCrew');
                    Route::delete('/crews/{crew}/subcrew/{subCrew}', [MenuCrewController::class, 'removeSubCrew'])->name('crews.removeSubCrew');
                    Route::delete('/crews/{crew}/parentcrew/{parentCrew}', [MenuCrewController::class, 'removeParentCrew'])->name('crews.removeParentCrew');
                    Route::post('/crews/{crew}/logo', [MenuCrewController::class, 'storeLogo'])->name('crews.storeLogo');
                    Route::delete('/crews/{crew}/logo', [MenuCrewController::class, 'destroyLogo'])->name('crews.destroyLogo');
                });

                Route::name('ajax.')->group(function () {
                    Route::prefix('/ajax')->group(function () {
                        Route::get('sndh.json', [SNDHController::class, 'sndh'])->name('sndh');
                        Route::get('users.json', [AjaxUserController::class, 'users'])->name('users');
                    });
                });
            });
        });
    });
});
