<?php

use App\Http\Controllers\Admin\Games\IssuesController;
use App\Http\Controllers\Admin\HomeController as AdminHomeController;
use App\Http\Controllers\Admin\Menus\MenuConditionsController;
use App\Http\Controllers\Admin\Menus\MenuContentTypesController;
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
                    Route::post('/disks/{disk}/screenshot', [MenuDisksController::class, 'addScreenshot'])->name('disks.addScreenshot');
                    Route::delete('/disks/{disk}/screenshot/{screenshot}', [MenuDisksController::class, 'destroyScreenshot'])->name('disks.deleteScreenshot');
                    Route::resource('conditions', MenuConditionsController::class);
                    Route::resource('content-types', MenuSoftwareContentTypesController::class);
                    Route::resource('software', MenuSoftwareController::class);
                });

            });
        });
    });
});
