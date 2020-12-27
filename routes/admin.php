<?php

use App\Http\Controllers\Admin\Games\IssuesController;
use App\Http\Controllers\Admin\HomeController as AdminHomeController;
use App\Http\Controllers\Admin\Menus\MenuConditionsController;
use App\Http\Controllers\Admin\Menus\MenuContentTypesController;
use App\Http\Controllers\Admin\Menus\MenusController;
use App\Http\Controllers\Admin\Menus\MenuSetsController;
use Illuminate\Support\Facades\Route;

Route::middleware('verified')->group(function () {
    Route::middleware('admin')->group(function () {
        Route::name('admin.')->group(function () {
            Route::prefix('/admin')->group(function () {
                Route::get('/', [AdminHomeController::class, 'index'])->name('home.index');

                Route::get('/games/issues', [IssuesController::class, 'index'])->name('games.issues');

                Route::post('/games/issues/genres', [IssuesController::class, 'setGenres'])->name('games.issues.genres');

                Route::prefix('/menus')->name('menus.')->group(function () {
                    Route::resource('sets', MenuSetsController::class);
                    Route::resource('conditions', MenuConditionsController::class);
                    Route::resource('content-types', MenuContentTypesController::class);
                });

            });
        });
    });
});
