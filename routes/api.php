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

// --- Public Routes ---
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// --- Protected Routes ---
Route::middleware('auth:sanctum')->group(function () {

    // Auth & Token Management
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Profile Management
    Route::prefix('profile')->group(function () {
        Route::get('/', [ProfileController::class, 'show']);
        Route::put('/', [ProfileController::class, 'update']);
    });

    // AI & Search Features
    Route::get('/search', [SearchController::class, 'index']);
    Route::post('/search/ai-playlist', [SearchController::class, 'generatePlaylist']);

    // Music Content
    Route::get('/genres', [GenreController::class, 'index']);
    Route::get('/genres/{slug}/songs', [GenreController::class, 'songs']);
    Route::get('/songs/popular', [SongController::class, 'popular']);
    Route::get('/songs/{id}', [SongController::class, 'show']);

    // Analytics & Streaming
    Route::post('/streams/log', [StreamController::class, 'log']);

    // Playlists Management
    Route::prefix('playlists')->group(function () {
        Route::get('/', [PlaylistController::class, 'index']);
        Route::post('/', [PlaylistController::class, 'store']);
        Route::post('/{id}/add-song', [PlaylistController::class, 'addSong']);
        Route::patch('/{id}/reorder', [PlaylistController::class, 'reorder']);
    });

    // Podcast Management
    Route::prefix('podcasts')->group(function () {
        Route::get('/', [PodcastController::class, 'index']);
        Route::get('/{id}', [PodcastController::class, 'show']);
        Route::post('/', [PodcastController::class, 'store']);
    });

    // Artist Management & Dashboard
    Route::prefix('artist')->group(function () {
        Route::post('/upgrade', [ArtistController::class, 'store']);
        Route::get('/dashboard', [ArtistDashboard::class, 'index']);
        Route::get('/{slug}', [ArtistController::class, 'show']);
    });

    // Admin Management & Dashboard
    Route::prefix('admin')->group(function () {
        Route::get('/dashboard', [AdminDashboard::class, 'index']);
        Route::get('/users', [UserController::class, 'index']);
        Route::patch('/users/{id}/toggle', [UserController::class, 'toggleStatus']);
    });
});