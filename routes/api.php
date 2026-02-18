<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ArtistController;
use App\Http\Controllers\Api\SongController;
use App\Http\Controllers\Api\AlbumController;
use App\Http\Controllers\Api\PlaylistController;
use App\Http\Controllers\Api\StreamController;
use App\Http\Controllers\Api\PodcastController;
use App\Http\Controllers\Api\PodcastEpisodeController;
use App\Http\Controllers\Api\GenreController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\HomepageController;
use App\Http\Controllers\Api\LikedSongController;
use App\Http\Controllers\Api\LyricController;
use App\Http\Controllers\Api\ContentReportController;
use App\Http\Controllers\Api\SongAiMetadataController;
use App\Http\Controllers\Api\ShareController;
use App\Http\Controllers\Api\Admin\DashboardController as AdminDashboard;
use App\Http\Controllers\Api\Admin\SongController as AdminSongController;
use App\Http\Controllers\Api\Artist\DashboardController as ArtistDashboard;

// ─── Public (no auth) ────────────────────────────────────────────────
Route::post('/register', [AuthController::class , 'register']);
Route::post('/login', [AuthController::class , 'login'])->name('login');
Route::post('/artist/register', [ArtistController::class , 'store']);

// Public browse
Route::get('/browse', [HomepageController::class, 'browse']);

// Public artist profile
Route::get('/artists/{slug}', [ArtistController::class , 'show']);

// Signed URL audio streaming (no auth, signature is the auth)
Route::get('/audio-stream/{id}', [StreamController::class , 'streamAudio'])
    ->name('stream.audio');

// ─── Authenticated ───────────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/refresh', [AuthController::class , 'refresh'])->name('auth.refresh');
    Route::post('/logout', [AuthController::class , 'logout']);

    Route::get('/feed', [HomepageController::class, 'feed']);

    // Profile
    Route::prefix('profile')->group(function () {
        Route::get('/', [ProfileController::class , 'show']);
        Route::put('/', [ProfileController::class , 'update']);
    });

    // Search
    Route::get('/search', [SearchController::class , 'index']);
    Route::post('/search/ai-playlist', [SearchController::class , 'generatePlaylist']);

    // Genres
    Route::prefix('genres')->group(function () {
        Route::get('/', [GenreController::class , 'index']);
        Route::post('/', [GenreController::class , 'store']);
        Route::put('/{id}', [GenreController::class , 'update']);
        Route::delete('/{id}', [GenreController::class , 'destroy']);
        Route::get('/{slug}/songs', [GenreController::class , 'songs']);
    });

    // Songs
    Route::get('/songs/popular', [SongController::class , 'popular']);
    Route::get('/songs/{id}', [SongController::class , 'show']);

    // Lyrics
    Route::get('/lyrics/{songId}', [LyricController::class , 'show']);

    // Streams
    Route::prefix('streams')->group(function () {
        Route::post('/log', [StreamController::class , 'log']);
        Route::get('/{id}/request-link', [StreamController::class , 'getSecureLink']);
    });

    // Playlists
    Route::prefix('playlists')->group(function () {
        Route::get('/', [PlaylistController::class , 'index']);
        Route::post('/', [PlaylistController::class , 'store']);
        Route::get('/{id}', [PlaylistController::class , 'show']);
        Route::put('/{id}', [PlaylistController::class , 'update']);
        Route::delete('/{id}', [PlaylistController::class , 'destroy']);
        Route::post('/{id}/add-song', [PlaylistController::class , 'addSong']);
        Route::delete('/{id}/songs/{songId}', [PlaylistController::class , 'removeSong']);
        Route::patch('/{id}/reorder', [PlaylistController::class , 'reorder']);
    });

    // Podcasts & Episodes
    Route::prefix('podcasts')->group(function () {
        Route::get('/', [PodcastController::class , 'index']);
        Route::post('/', [PodcastController::class , 'store']);
        Route::get('/{id}', [PodcastController::class , 'show']);
        Route::put('/{id}', [PodcastController::class , 'update']);
        Route::delete('/{id}', [PodcastController::class , 'destroy']);

        // Nested episodes
        Route::get('/{podcastId}/episodes', [PodcastEpisodeController::class , 'index']);
        Route::post('/{podcastId}/episodes', [PodcastEpisodeController::class , 'store']);
        Route::get('/{podcastId}/episodes/{episodeId}', [PodcastEpisodeController::class , 'show']);
        Route::put('/{podcastId}/episodes/{episodeId}', [PodcastEpisodeController::class , 'update']);
        Route::delete('/{podcastId}/episodes/{episodeId}', [PodcastEpisodeController::class , 'destroy']);
    });

    // Liked Songs
    Route::prefix('liked-songs')->group(function () {
        Route::get('/', [LikedSongController::class , 'index']);
        Route::post('/', [LikedSongController::class , 'store']);
        Route::delete('/{songId}', [LikedSongController::class , 'destroy']);
    });

    // Song AI Metadata
    Route::prefix('songs/{songId}/ai-metadata')->group(function () {
        Route::get('/', [SongAiMetadataController::class , 'show']);
        Route::post('/', [SongAiMetadataController::class , 'store']);
        Route::put('/', [SongAiMetadataController::class , 'update']);
        Route::delete('/', [SongAiMetadataController::class , 'destroy']);
    });

    // Content Reports
    Route::prefix('reports')->group(function () {
        Route::get('/', [ContentReportController::class , 'index']);
        Route::post('/', [ContentReportController::class , 'store']);
        Route::patch('/{id}', [ContentReportController::class , 'update']);
        Route::delete('/{id}', [ContentReportController::class , 'destroy']);
    });

    // Share
    Route::prefix('share')->group(function () {
        Route::get('/song/{id}', [ShareController::class , 'shareSong']);
        Route::get('/playlist/{id}', [ShareController::class , 'sharePlaylist']);
    });

    // ─── Artist Routes (requires artist role) ────────────────────────
    Route::prefix('artist')->middleware('artist')->group(function () {
        Route::get('/dashboard', [ArtistDashboard::class , 'index']);
        Route::get('/{slug}', [ArtistController::class , 'show']);

        // Songs management
        Route::get('/songs', [SongController::class , 'index']);
        Route::post('/songs', [SongController::class , 'store']);
        Route::put('/songs/{id}', [SongController::class , 'update']);
        Route::delete('/songs/{id}', [SongController::class , 'destroy']);

        // Albums management
        Route::get('/albums', [AlbumController::class , 'index']);
        Route::post('/albums', [AlbumController::class , 'store']);
        Route::get('/albums/{id}', [AlbumController::class , 'show']);
        Route::put('/albums/{id}', [AlbumController::class , 'update']);
        Route::delete('/albums/{id}', [AlbumController::class , 'destroy']);
    });

    // Upgrade to artist (any authenticated user)
    Route::post('/artist/upgrade', [ArtistController::class , 'upgrade']);

    // ─── Admin Routes (requires admin role) ──────────────────────────
    Route::prefix('admin')->middleware('admin')->group(function () {
        Route::get('/dashboard', [AdminDashboard::class , 'index']);

        // User management
        Route::get('/users', [UserController::class , 'index']);
        Route::get('/users/{id}', [UserController::class , 'show']);
        Route::patch('/users/{id}/toggle', [UserController::class , 'toggleStatus']);

        // Song moderation
        Route::get('/songs', [AdminSongController::class , 'index']);
        Route::patch('/songs/{id}/approve', [AdminSongController::class , 'approve']);
        Route::patch('/songs/{id}/reject', [AdminSongController::class , 'reject']);
        Route::delete('/songs/{id}', [AdminSongController::class , 'destroy']);
    });
});