<?php

use App\Http\Controllers\Admin\Games\IssuesController;
use App\Http\Controllers\Admin\HomeController as AdminHomeController;
use App\Http\Controllers\Admin\Menus\MenuConditionsController;
use App\Http\Controllers\Admin\Menus\MenuCrewController;
use App\Http\Controllers\Admin\Menus\MenuDisksContentController;
use App\Http\Controllers\Admin\Menus\MenuDisksController;
use App\Http\Controllers\Admin\Menus\MenusController;
use App\Http\Controllers\Admin\Menus\MenuSetsController;
use App\Http\Controllers\Admin\Menus\MenuSoftwareContentTypesController;
use App\Http\Controllers\Admin\Menus\MenuSoftwareController;
use Illuminate\Support\Facades\Route;

Route::middleware('verified')->group(function () {
    Route::middleware('admin')->group(function () {
        Route::name('admin.')->group(function () {
            Route::prefix('/admin')->group(function () {
                Route::get('/', [AdminHomeController::class, 'index'])->name('home.index');

                Route::get('/games/issues', [IssuesController::class, 'index'])->name('games.issues');

                Route::post('/games/issues/genres/{game}', [IssuesController::class, 'setGenres'])->name('games.issues.genres');

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
            });
        });
    });
});
