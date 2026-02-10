<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ArtistController;
use App\Http\Controllers\Api\SongController;
use App\Http\Controllers\Api\PlaylistController;
use App\Http\Controllers\Api\StreamController;
use App\Http\Controllers\Api\PodcastController;
use App\Http\Controllers\Api\GenreController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\Admin\DashboardController as AdminDashboard;
use App\Http\Controllers\Api\Artist\DashboardController as ArtistDashboard;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {

    Route::post('/refresh', [AuthController::class, 'refresh'])->name('auth.refresh');
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::prefix('profile')->group(function () {
        Route::get('/', [ProfileController::class, 'show']);
        Route::put('/', [ProfileController::class, 'update']);
    });

    Route::get('/search', [SearchController::class, 'index']);
    Route::post('/search/ai-playlist', [SearchController::class, 'generatePlaylist']);

    Route::get('/genres', [GenreController::class, 'index']);
    Route::get('/genres/{slug}/songs', [GenreController::class, 'songs']);
    Route::get('/songs/popular', [SongController::class, 'popular']);
    Route::get('/songs/{id}', [SongController::class, 'show']);

    Route::prefix('streams')->group(function () {
        Route::post('/log', [StreamController::class, 'log']);
        Route::get('/{id}/request-link', [StreamController::class, 'getSecureLink']);
    });

    Route::get('/audio-stream/{id}', [StreamController::class, 'streamAudio'])
        ->name('stream.audio')
        ->middleware('signed');

    Route::prefix('playlists')->group(function () {
        Route::get('/', [PlaylistController::class, 'index']);
        Route::post('/', [PlaylistController::class, 'store']);
        Route::post('/{id}/add-song', [PlaylistController::class, 'addSong']);
        Route::patch('/{id}/reorder', [PlaylistController::class, 'reorder']);
    });

    Route::prefix('podcasts')->group(function () {
        Route::get('/', [PodcastController::class, 'index']);
        Route::get('/{id}', [PodcastController::class, 'show']);
        Route::post('/', [PodcastController::class, 'store']);
    });

    Route::prefix('artist')->group(function () {
        Route::post('/upgrade', [ArtistController::class, 'store']);
        Route::get('/dashboard', [ArtistDashboard::class, 'index']);
        Route::get('/{slug}', [ArtistController::class, 'show']);
        Route::post('/songs', [SongController::class, 'store']);
    });

    Route::prefix('admin')->group(function () {
        Route::get('/dashboard', [AdminDashboard::class, 'index']);
        Route::get('/users', [UserController::class, 'index']);
        Route::patch('/users/{id}/toggle', [UserController::class, 'toggleStatus']);
    });
});