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

Route::middleware('auth:web')->group(function () {
    Route::post('/news/submit', 'NewsController@postNews')->name('news.submit');
    Route::post('/games/{game}/comment', 'GameController@postComment')->name('games.comment');
    Route::post('/games/submitInfo', 'GameController@submitInfo')->name('games.submitInfo');
    Route::get('/reviews/submit', 'ReviewController@prepareSubmit')->name('reviews.submit');
    Route::post('/reviews/submit', 'ReviewController@submit')->name('reviews.submit');
    Route::post('/reviews/{review}/comment', 'ReviewController@postComment')->name('review.comment');
    Route::post('/interviews/{interview}/comment', 'InterviewController@postComment')->name('interview.comment');
    Route::post('/articles/{article}/comment', 'ArticleController@postComment')->name('article.comment');
    Route::post('/links/submit', 'LinkController@postLink')->name('links.submit');
});

Route::resource('/news', 'NewsController')->only(['index']);

Route::get('/games', 'GameController@index')->name('games.index');
Route::get('/games/search', 'GameController@search')->name('games.search');
Route::get('/games/{game}', 'GameController@show')->name('games.show');

Route::resource('/reviews', 'ReviewController')->only(['index', 'show']);

Route::resource('/interviews', 'InterviewController')->only(['index', 'show']);

Route::resource('/articles', 'ArticleController')->only(['index', 'show']);

Route::resource('/links', 'LinkController')->only(['index']);

Route::resource('/about', 'AboutController')->only(['index']);
Route::get('/about/andreas', 'AboutController@andreas')->name('about.andreas');

Route::prefix('/ajax')->group(function () {
    Route::get('companies.json', 'Ajax\CompanyController@companies');
    Route::get('release-years.json', 'Ajax\ReleaseYearController@releaseYears');
    Route::get('genres.json', 'Ajax\GenreController@genres');
    Route::get('games.json', 'Ajax\GameController@games');
});

Auth::routes();
