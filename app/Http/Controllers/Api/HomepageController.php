<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Song;
use App\Models\Genre;
use App\Models\Playlist;
use App\Models\Podcast;
use App\Models\StreamHistory;
use Illuminate\Http\JsonResponse;

class HomepageController extends Controller
{
    /**
     * Public browse — for guest users (no auth required).
     * Returns popular songs, latest songs, and genres.
     * Songs are visible but cannot be played (no stream access without auth).
     */
    public function browse(): JsonResponse
    {
        $popularSongs = Song::select(['id', 'artist_id', 'album_id', 'title', 'slug', 'cover_url', 'duration_seconds', 'stream_count'])
            ->with(['artist:id,name,slug', 'album:id,title,cover_image_url'])
            ->orderBy('stream_count', 'desc')
            ->limit(10)
            ->get();

        $latestSongs = Song::select(['id', 'artist_id', 'album_id', 'title', 'slug', 'cover_url', 'duration_seconds', 'stream_count'])
            ->with(['artist:id,name,slug', 'album:id,title,cover_image_url'])
            ->latest()
            ->limit(10)
            ->get();

        $genres = Genre::select(['id', 'name', 'slug'])->get();

        return response()->json([
            'popular_songs' => $popularSongs,
            'latest_songs' => $latestSongs,
            'genres' => $genres,
            'is_authenticated' => false,
        ]);
    }

    /**
     * Authenticated feed — for logged-in users.
     * Returns recently played, latest songs, popular songs, and podcasts.
     */
    public function feed(): JsonResponse
    {
        $userId = auth()->id();

        // 1. Recently played songs (from stream_history)
        $recentlyPlayed = StreamHistory::where('user_id', $userId)
            ->select(['song_id', 'played_at'])
            ->orderBy('played_at', 'desc')
            ->limit(20)
            ->get()
            ->unique('song_id')
            ->take(10);

        $recentSongIds = $recentlyPlayed->pluck('song_id');

        $recentSongs = collect();
        if ($recentSongIds->isNotEmpty()) {
            $recentSongs = Song::whereIn('id', $recentSongIds)
                ->select(['id', 'artist_id', 'album_id', 'title', 'slug', 'cover_url', 'duration_seconds', 'stream_count', 'file_path'])
                ->with(['artist:id,name,slug', 'album:id,title,cover_image_url'])
                ->get()
                ->sortBy(function ($song) use ($recentSongIds) {
                    return array_search($song->id, $recentSongIds->toArray());
                })
                ->values();
        }

        // 2. Latest songs
        $latestSongs = Song::select(['id', 'artist_id', 'album_id', 'title', 'slug', 'cover_url', 'duration_seconds', 'stream_count', 'file_path'])
            ->with(['artist:id,name,slug', 'album:id,title,cover_image_url'])
            ->latest()
            ->limit(10)
            ->get();

        // 3. Popular songs
        $popularSongs = Song::select(['id', 'artist_id', 'album_id', 'title', 'slug', 'cover_url', 'duration_seconds', 'stream_count', 'file_path'])
            ->with(['artist:id,name,slug', 'album:id,title,cover_image_url'])
            ->orderBy('stream_count', 'desc')
            ->limit(10)
            ->get();

        // 4. Podcasts
        $podcasts = Podcast::select(['id', 'artist_id', 'title', 'cover_image_url', 'category'])
            ->with(['artist:id,name'])
            ->latest()
            ->limit(10)
            ->get();

        // 5. Made For You — public/AI-generated playlists
        $madeForYou = Playlist::select(['id', 'user_id', 'name', 'description', 'cover_url', 'is_ai_generated'])
            ->where('is_public', true)
            ->with(['user:id,username'])
            ->withCount('songs')
            ->latest()
            ->limit(10)
            ->get();

        return response()->json([
            'recently_played' => $recentSongs,
            'latest_songs' => $latestSongs,
            'popular_songs' => $popularSongs,
            'podcasts' => $podcasts,
            'made_for_you' => $madeForYou,
            'is_authenticated' => true,
        ]);
    }
}
