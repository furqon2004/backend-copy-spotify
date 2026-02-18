<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LikedSongController extends Controller
{
    public function index(): JsonResponse
    {
        $liked = DB::table('liked_songs')
            ->where('liked_songs.user_id', auth()->id())
            ->join('songs', 'liked_songs.song_id', '=', 'songs.id')
            ->join('artists', 'songs.artist_id', '=', 'artists.id')
            ->leftJoin('albums', 'songs.album_id', '=', 'albums.id')
            ->select([
                'songs.id',
                'songs.artist_id',
                'songs.title',
                'songs.cover_url',
                'songs.duration_seconds',
                'artists.name as artist_name',
                'artists.slug as artist_slug',
                'albums.title as album_title',
                'liked_songs.liked_at'
            ])
            ->orderByDesc('liked_songs.liked_at')
            ->paginate(20);

        return response()->json($liked);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'song_id' => 'required|uuid|exists:songs,id',
        ]);

        $userId = auth()->id();
        $songId = $request->song_id;

        $exists = DB::table('liked_songs')
            ->where('user_id', $userId)
            ->where('song_id', $songId)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Song already liked'], 409);
        }

        DB::table('liked_songs')->insert([
            'user_id' => $userId,
            'song_id' => $songId,
            'liked_at' => now(),
        ]);

        return response()->json(['message' => 'Song liked'], 201);
    }

    public function destroy(string $songId): JsonResponse
    {
        $deleted = DB::table('liked_songs')
            ->where('user_id', auth()->id())
            ->where('song_id', $songId)
            ->delete();

        if (!$deleted) {
            return response()->json(['message' => 'Song not found in liked list'], 404);
        }

        return response()->json(['message' => 'Song unliked']);
    }
}
