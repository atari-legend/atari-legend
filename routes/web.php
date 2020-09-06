<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

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

Route::get('/', 'HomeController@index')->name('home.index');
Route::resource('/news', 'NewsController')->only(['index']);
Route::get('/games', 'GameController@index')->name('games.index');
Route::get('/games/search', 'GameController@search');
Route::get('/games/{game}', 'GameController@show')->name('games.show');
Route::resource('/reviews', 'ReviewController')->only(['index', 'show']);
Route::post('/reviews/{review}/comment', 'ReviewController@postComment')->name('review.comment');
Route::resource('/interviews', 'InterviewController')->only(['index', 'show']);
Route::resource('/articles', 'ArticleController')->only(['index']);
Route::resource('/links', 'LinkController')->only(['index']);
Route::resource('/about', 'AboutController')->only(['index']);

Auth::routes();
