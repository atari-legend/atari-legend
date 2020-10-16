<?php

use App\Http\Controllers\Ajax\CompanyController;
use App\Http\Controllers\Ajax\GameController as AjaxGameController;
use App\Http\Controllers\Ajax\GenreController;
use App\Http\Controllers\Ajax\ReleaseYearController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\LinkController;
use App\Http\Controllers\InterviewController;
use App\Http\Controllers\AboutController;
use App\Http\Controllers\RobotsController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ArticleController;
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

Route::get('/', [HomeController::class, 'index'])->name('home.index');

Route::middleware('auth:web')->group(function () {
    Route::post('/news/submit', [NewsController::class, 'postNews'])->name('news.submit');
    Route::post('/games/{game}/comment', [GameController::class, 'postComment'])->name('games.comment');
    Route::post('/games/submitInfo', [GameController::class, 'submitInfo'])->name('games.submitInfo');
    Route::get('/reviews/submit', [ReviewController::class, 'prepareSubmit'])->name('reviews.submit');
    Route::post('/reviews/submit', [ReviewController::class, 'submit'])->name('reviews.submit');
    Route::post('/reviews/{review}/comment', [ReviewController::class, 'postComment'])->name('review.comment');
    Route::post('/interviews/{interview}/comment', [InterviewController::class, 'postComment'])->name('interview.comment');
    Route::post('/articles/{article}/comment', [ArticleController::class, 'postComment'])->name('article.comment');
    Route::post('/links/submit', [LinkController::class, 'postLink'])->name('links.submit');
    Route::post('/comments/delete', [CommentController::class, 'delete'])->name('comments.delete');
    Route::post('/comments/update', [CommentController::class, 'update'])->name('comments.update');
});

Route::resource('/news', NewsController::class)->only(['index']);

Route::get('/games', [GameController::class, 'index'])->name('games.index');
Route::get('/games/search', [GameController::class, 'search'])->name('games.search');
Route::get('/games/{game}', [GameController::class, 'show'])->name('games.show');

Route::resource('/reviews', ReviewController::class)->only(['index', 'show']);

Route::resource('/interviews', InterviewController::class)->only(['index', 'show']);

Route::resource('/articles', ArticleController::class)->only(['index', 'show']);

Route::resource('/links', LinkController::class)->only(['index']);

Route::resource('/about', AboutController::class)->only(['index']);
Route::get('/about/andreas', [AboutController::class, 'andreas'])->name('about.andreas');

Route::get('/feed', [FeedController::class, 'feed'])->name('feed');

Route::get('/sitemap', [SitemapController::class, 'index'])->name('sitemap.index');
Route::get('/sitemap/general', [SitemapController::class, 'general'])->name('sitemap.general');
Route::get('/sitemap/games/{letter}', [SitemapController::class, 'games'])->name('sitemap.games');

Route::get('/robots.txt', [RobotsController::class, 'index']);

Route::prefix('/ajax')->group(function () {
    Route::get('companies.json', [CompanyController::class, 'companies']);
    Route::get('release-years.json', [ReleaseYearController::class, 'releaseYears']);
    Route::get('genres.json', [GenreController::class, 'genres']);
    Route::get('games.json', [AjaxGameController::class, 'games']);
});

Auth::routes();
