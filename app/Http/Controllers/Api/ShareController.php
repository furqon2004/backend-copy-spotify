<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Song;
use App\Models\Playlist;
use Illuminate\Http\JsonResponse;

class ShareController extends Controller
{
    /**
     * Generate a shareable link for a song.
     */
    public function shareSong(string $id): JsonResponse
    {
        $song = Song::with('artist:id,name,slug')
            ->select(['id', 'title', 'slug', 'cover_url', 'artist_id'])
            ->findOrFail($id);

        $shareUrl = config('app.frontend_url', config('app.url')) . '/song/' . $song->id;

        return response()->json([
            'share_url' => $shareUrl,
            'song' => $song,
            'message' => "Listen to \"{$song->title}\" by {$song->artist->name}",
        ]);
    }

    /**
     * Generate a shareable link for a playlist.
     */
    public function sharePlaylist(string $id): JsonResponse
    {
        $playlist = Playlist::with('user:id,username')
            ->select(['id', 'user_id', 'name', 'description', 'cover_url', 'is_public'])
            ->findOrFail($id);

        if (!$playlist->is_public) {
            return response()->json(['message' => 'This playlist is private and cannot be shared.'], 403);
        }

        $shareUrl = config('app.frontend_url', config('app.url')) . '/playlist/' . $playlist->id;

        return response()->json([
            'share_url' => $shareUrl,
            'playlist' => $playlist,
            'message' => "Check out \"{$playlist->name}\" playlist",
        ]);
    }
}
