<?php

use App\Http\Controllers\Admin\Ajax\GameController as AjaxGameController;
use App\Http\Controllers\Admin\Ajax\SNDHController;
use App\Http\Controllers\Admin\Ajax\UserController as AjaxUserController;
use App\Http\Controllers\Admin\Articles\ArticleTypeController;
use App\Http\Controllers\Admin\Games\GameCompanyController;
use App\Http\Controllers\Admin\Games\GameConfigurationController;
use App\Http\Controllers\Admin\Games\GameController;
use App\Http\Controllers\Admin\Games\GameCreditsController;
use App\Http\Controllers\Admin\Games\GameFactsController;
use App\Http\Controllers\Admin\Games\GameIndividualController;
use App\Http\Controllers\Admin\Games\GameMusicController;
use App\Http\Controllers\Admin\Games\GameReleaseController;
use App\Http\Controllers\Admin\Games\GameScreenshotsController;
use App\Http\Controllers\Admin\Games\GameSeriesController;
use App\Http\Controllers\Admin\Games\GameSimilarController;
use App\Http\Controllers\Admin\Games\GameSubmissionController;
use App\Http\Controllers\Admin\Games\GameVideoController;
use App\Http\Controllers\Admin\Games\IssuesController;
use App\Http\Controllers\Admin\Games\MusicController;
use App\Http\Controllers\Admin\Games\Releases\ReleaseMediasController;
use App\Http\Controllers\Admin\Games\Releases\ReleaseMediasDumpsController;
use App\Http\Controllers\Admin\Games\Releases\ReleaseMediasScansController;
use App\Http\Controllers\Admin\Games\Releases\ReleaseScansController;
use App\Http\Controllers\Admin\Games\Releases\ReleaseSceneController;
use App\Http\Controllers\Admin\Games\Releases\ReleaseSystemInfoController;
use App\Http\Controllers\Admin\HomeController as AdminHomeController;
use App\Http\Controllers\Admin\Magazines\MagazineIndexTypesController;
use App\Http\Controllers\Admin\Magazines\MagazineIssuesController;
use App\Http\Controllers\Admin\Magazines\MagazinesController;
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
use App\Http\Controllers\Admin\Other\SpotlightController;
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

                    Route::prefix('/{game}')->group(function () {
                        Route::get('music', [GameMusicController::class, 'index'])->name('game-music.index');
                        Route::post('music', [GameMusicController::class, 'store'])->name('game-music.store');
                        Route::post('music/associate', [GameMusicController::class, 'associate'])->name('game-music.associate');
                        Route::delete('music/{sndh}', [GameMusicController::class, 'destroy'])
                            ->where(['sndh' => '[\w\s\-_\/()]+'])
                            ->name('game-music.destroy');

                        Route::get('credits', [GameCreditsController::class, 'index'])->name('game-credits.index');
                        Route::post('credits', [GameCreditsController::class, 'store'])->name('game-credits.store');
                        Route::delete('credits/{individual}', [GameCreditsController::class, 'destroy'])->name('game-credits.destroy');
                        Route::post('developers', [GameCreditsController::class, 'storeDeveloper'])->name('game-developers.store');
                        Route::delete('developers/{developer}', [GameCreditsController::class, 'destroyDeveloper'])->name('game-developers.destroy');

                        Route::get('facts', [GameFactsController::class, 'index'])->name('game-facts.index');
                        Route::delete('facts/{fact}', [GameFactsController::class, 'destroy'])->name('game-facts.destroy');
                        Route::post('facts', [GameFactsController::class, 'store'])->name('game-facts.store');
                        Route::get('facts/create', [GameFactsController::class, 'create'])->name('game-facts.create');
                        Route::put('facts/{fact}', [GameFactsController::class, 'update'])->name('game-facts.update');
                        Route::get('facts/{fact}', [GameFactsController::class, 'edit'])->name('game-facts.edit');

                        Route::get('screenshots', [GameScreenshotsController::class, 'index'])->name('game-screenshots.index');
                        Route::post('screenshots', [GameScreenshotsController::class, 'store'])->name('game-screenshots.store');
                        Route::delete('screenshots/{screenshot}', [GameScreenshotsController::class, 'destroy'])->name('game-screenshots.destroy');

                        Route::get('videos', [GameVideoController::class, 'index'])->name('game-videos.index');
                        Route::post('videos', [GameVideoController::class, 'store'])->name('game-videos.store');
                        Route::delete('videos/{video}', [GameVideoController::class, 'destroy'])->name('game-videos.destroy');

                        Route::get('similar', [GameSimilarController::class, 'index'])->name('game-similar.index');
                        Route::post('similar', [GameSimilarController::class, 'store'])->name('game-similar.store');
                        Route::delete('similar/{similar}', [GameSimilarController::class, 'destroy'])->name('game-similar.destroy');

                        Route::post('update/base-info', [GameController::class, 'update'])->name('games.update.base-info');
                        Route::post('update/multiplayer', [GameController::class, 'updateMultiplayer'])->name('games.update.multiplayer');
                        Route::post('aka', [GameController::class, 'storeAka'])->name('games.aka.store');
                        Route::delete('aka/{aka}/destroy', [GameController::class, 'destroyAka'])->name('games.destroy.aka');
                        Route::post('vs', [GameController::class, 'storeVs'])->name('games.vs.store');
                        Route::delete('vs/{vs}/destroy', [GameController::class, 'destroyVs'])->name('games.destroy.vs');

                        Route::resource('releases', GameReleaseController::class);

                        Route::prefix('/{release}')->name('releases.')->group(function () {
                            Route::get('scene', [ReleaseSceneController::class, 'index'])->name('scene.index');
                            Route::post('scene', [ReleaseSceneController::class, 'update'])->name('scene.update');
                            Route::get('system', [ReleaseSystemInfoController::class, 'index'])->name('system.index');
                            Route::post('system', [ReleaseSystemInfoController::class, 'update'])->name('system.update');
                            Route::post('aka', [GameReleaseController::class, 'storeAka'])->name('aka.store');
                            Route::delete('aka/{aka}', [GameReleaseController::class, 'destroyAka'])->name('aka.destroy');
                            Route::resource('medias', ReleaseMediasController::class);
                            Route::resource('scans', ReleaseScansController::class);

                            Route::prefix('/{media}')->name('medias.')->group(function () {
                                Route::resource('scans', ReleaseMediasScansController::class);
                                Route::resource('dumps', ReleaseMediasDumpsController::class);
                            });
                        });
                    });

                    Route::resource('games', GameController::class);

                    Route::resource('submissions', GameSubmissionController::class);
                    Route::delete('submissions/{submission}/screenshots/{screenshot}', [GameSubmissionController::class, 'destroyScreenshot'])->name('submissions.screenshots.destroy');

                    Route::delete('individuals/{individual}/avatar', [GameIndividualController::class, 'destroyAvatar'])->name('individuals.avatar.destroy');
                    Route::post('individuals/{individual}/nickname', [GameIndividualController::class, 'storeNickname'])->name('individuals.nickname.store');
                    Route::delete('individuals/{individual}/nickname/{nickname}', [GameIndividualController::class, 'destroyNickname'])->name('individuals.nickname.destroy');
                    Route::resource('individuals', GameIndividualController::class);

                    Route::delete('companies/{company}/logo', [GameCompanyController::class, 'destroyLogo'])->name('companies.logo.destroy');
                    Route::resource('companies', GameCompanyController::class);

                    Route::delete('series/{series}/game/{game}', [GameSeriesController::class, 'removeGame'])->name('series.game.destroy');
                    Route::post('series/{series}/game', [GameSeriesController::class, 'addGame'])->name('series.game.store');
                    Route::resource('series', GameSeriesController::class);

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

                Route::prefix('/articles')->name('articles.')->group(function () {
                    Route::resource('types', ArticleTypeController::class);
                });

                Route::prefix('/others')->name('others.')->group(function () {
                    Route::resource('trivias', TriviaController::class);
                    Route::resource('quotes', QuoteController::class);

                    Route::delete('spotlights/{spotlight}/image', [SpotlightController::class, 'destroyImage'])->name('spotlights.image.destroy');
                    Route::resource('spotlights', SpotlightController::class);
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

                Route::prefix('/magazines')->name('magazines.')->group(function () {
                    Route::resource('magazines', MagazinesController::class);
                    Route::resource('magazines/{magazine}/issues', MagazineIssuesController::class);
                    Route::resource('index-types', MagazineIndexTypesController::class);
                });

                Route::name('ajax.')->group(function () {
                    Route::prefix('/ajax')->group(function () {
                        Route::get('sndh.json', [SNDHController::class, 'sndh'])->name('sndh');
                        Route::get('users.json', [AjaxUserController::class, 'users'])->name('users');
                        Route::get('games.json', [AjaxGameController::class, 'games'])->name('games');
                    });
                });
            });
        });
    });
});
