<?php

use App\Http\Controllers\AboutController;
use App\Http\Controllers\Ajax\CompanyController;
use App\Http\Controllers\Ajax\CrewController;
use App\Http\Controllers\Ajax\EngineController;
use App\Http\Controllers\Ajax\GameAndSoftwareController;
use App\Http\Controllers\Ajax\GameController as AjaxGameController;
use App\Http\Controllers\Ajax\GenreController;
use App\Http\Controllers\Ajax\IndividualController;
use App\Http\Controllers\Ajax\ReleaseYearController;
use App\Http\Controllers\Ajax\SoftwareController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\Auth\UserController;
use App\Http\Controllers\ChangelogController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\GameMusicController;
use App\Http\Controllers\GameReleaseController;
use App\Http\Controllers\GameReleaseResourcesController;
use App\Http\Controllers\GameResourcesController;
use App\Http\Controllers\GameSearchController;
use App\Http\Controllers\GameVoteController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\IndividualResourcesController;
use App\Http\Controllers\InterviewController;
use App\Http\Controllers\LinkController;
use App\Http\Controllers\MagazineController;
use App\Http\Controllers\MenuSetController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\RobotsController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\SpotlightResourcesController;
use App\Http\Controllers\WebsiteResourcesController;
use App\Models\Game;
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

Route::middleware('verified')->group(function () {
    Route::get('/', [HomeController::class, 'index'])->name('home.index');

    Route::middleware('auth:web')->group(function () {
        Route::post('/news/submit', [NewsController::class, 'postNews'])->name('news.submit');
        Route::post('/games/{game:slug}/comment', [GameController::class, 'postComment'])->name('games.comment');
        Route::post('/games/{game:slug}/submitInfo', [GameController::class, 'submitInfo'])->name('games.submitInfo');
        Route::post('/games/{game:slug}/vote', [GameVoteController::class, 'vote'])->name('games.vote');
        Route::get('/reviews/submit', [ReviewController::class, 'edit'])->name('reviews.edit');
        Route::post('/reviews/submit', [ReviewController::class, 'submit'])->name('reviews.submit');
        Route::post('/reviews/{review}/comment', [ReviewController::class, 'postComment'])->name('review.comment');
        Route::post('/interviews/{interview}/comment', [InterviewController::class, 'postComment'])->name('interview.comment');
        Route::post('/articles/{article}/comment', [ArticleController::class, 'postComment'])->name('article.comment');
        Route::post('/links/submit', [LinkController::class, 'postLink'])->name('links.submit');
        Route::post('/comments/delete', [CommentController::class, 'delete'])->name('comments.delete');
        Route::post('/comments/update', [CommentController::class, 'update'])->name('comments.update');

        Route::get('/profile', [UserController::class, 'profile'])->name('auth.profile');
        Route::post('/profile', [UserController::class, 'update'])->name('auth.update');
        Route::post('/profile/password', [UserController::class, 'password'])->name('auth.password');
    });

    Route::resource('/news', NewsController::class)->only(['index']);

    Route::get('/games', [GameSearchController::class, 'index'])->name('games.index');
    Route::get('/games/search', [GameSearchController::class, 'search'])->name('games.search');
    Route::get('/games/release/{release}', [GameReleaseController::class, 'show'])->name('games.releases.show');
    Route::get('/games/release/{release}/boxscan-{id}.webp', [GameReleaseResourcesController::class, 'boxscan'])
        ->name('games.releases.boxscan');

    Route::get('/games/{id}', fn ($id) => redirect('/games/' . Game::findOrFail($id)->slug, 301));
    Route::get('/games/{game:slug}', [GameController::class, 'show'])->name('games.show');

    Route::get('/games/{game:slug}/screenshot-{id}.{ext}', [GameResourcesController::class, 'screenshot'])->name('games.screenshot');

    Route::get('/music/cover/{game:slug}', [GameMusicController::class, 'cover'])->name('music.cover');
    Route::get('/music/{sndh}', [GameMusicController::class, 'music'])
        ->where(['sndh' => '[\w\s\-_\/()]+'])
        ->name('music');

    Route::get('/menusets', [MenuSetController::class, 'index'])->name('menus.index');
    Route::get('/menusets/software/{software}', [MenuSetController::class, 'software'])->name('menus.software');
    Route::get('/menusets/search', [MenuSetController::class, 'search'])->name('menus.search');
    Route::get('/menusets/{set}', [MenuSetController::class, 'show'])->name('menus.show');
    Route::get('/menusets/{set}/scrolltexts.epub', [MenuSetController::class, 'epub'])->name('menus.epub');

    Route::resource('/reviews', ReviewController::class)->only(['index', 'show']);

    Route::resource('/interviews', InterviewController::class)->only(['index', 'show']);

    Route::resource('/articles', ArticleController::class)->only(['index', 'show']);

    Route::resource('/links', LinkController::class)->only(['index']);

    Route::resource('/about', AboutController::class)->only(['index']);

    Route::resource('/magazines', MagazineController::class);

    Route::get('/about/andreas', [AboutController::class, 'andreas'])->name('about.andreas');

    Route::get('/changelog', [ChangelogController::class, 'index'])->name('changelog.index');

    Route::get('/individuals/{individual}/avatar.webp', [IndividualResourcesController::class, 'avatar'])->name('individuals.avatar');
    Route::get('/spotlights/{spotlight}/spotlight.webp', [SpotlightResourcesController::class, 'screenshot'])->name('spotlights.screenshot');
    Route::get('/websites/{website}/screenshot.webp', [WebsiteResourcesController::class, 'screenshot'])->name('websites.screenshot');

    Route::get('/sitemap', [SitemapController::class, 'index'])->name('sitemap.index');
    Route::get('/sitemap/general', [SitemapController::class, 'general'])->name('sitemap.general');
    Route::get('/sitemap/games/{letter}', [SitemapController::class, 'games'])->name('sitemap.games');

    Route::get('/robots.txt', [RobotsController::class, 'index']);

    Route::name('ajax.')->group(function () {
        Route::prefix('/ajax')->group(function () {
            Route::get('companies.json', [CompanyController::class, 'companies'])->name('companies');
            Route::get('release-years.json', [ReleaseYearController::class, 'releaseYears'])->name('release-years');
            Route::get('genres.json', [GenreController::class, 'genres'])->name('genres');
            Route::get('engines.json', [EngineController::class, 'engines'])->name('engines');
            Route::get('games.json', [AjaxGameController::class, 'games'])->name('games');
            Route::get('games-and-software.json', [GameAndSoftwareController::class, 'gamesAndSoftware'])->name('games-and-software');
            Route::get('software.json', [SoftwareController::class, 'software'])->name('software');
            Route::get('individuals.json', [IndividualController::class, 'individuals'])->name('individuals');
            Route::get('crews.json', [CrewController::class, 'crews'])->name('crews');
        });
    });
});

Auth::routes(['verify' => true]);
Route::feeds('feed');
