<?php

use App\Http\Controllers\Admin\Games\IssuesController;
use App\Http\Controllers\Admin\HomeController as AdminHomeController;
use Illuminate\Support\Facades\Route;

Route::middleware('verified')->group(function () {
    Route::middleware('admin')->group(function () {
        Route::name('admin.')->group(function () {
            Route::prefix('/admin')->group(function () {
                Route::get('/', [AdminHomeController::class, 'index'])->name('home.index');

                Route::get('/games/issues', [IssuesController::class, 'index'])->name('games.issues');
                Route::post('/games/issues/genres/{game}', [IssuesController::class, 'setGenres'])->name('games.issues.genres');
            });
        });
    });
});
