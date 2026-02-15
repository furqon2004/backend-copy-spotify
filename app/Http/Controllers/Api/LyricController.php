<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Song;
use Illuminate\Http\JsonResponse;

class LyricController extends Controller
{
    /**
     * Get lyrics for a song.
     */
    public function show(string $songId): JsonResponse
    {
        $song = Song::select(['id', 'title', 'artist_id'])
            ->with(['artist:id,name', 'lyric'])
            ->findOrFail($songId);

        if (!$song->lyric) {
            return response()->json(['message' => 'Lyrics not available for this song.'], 404);
        }

        return response()->json([
            'song' => [
                'id' => $song->id,
                'title' => $song->title,
                'artist' => $song->artist->name ?? null,
            ],
            'lyrics' => [
                'content' => $song->lyric->content,
                'synced_lyrics' => $song->lyric->synced_lyrics,
                'language' => $song->lyric->language,
                'source' => $song->lyric->source,
            ],
        ]);
    }
}
