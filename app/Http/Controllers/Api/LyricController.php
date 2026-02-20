<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Song;
use App\Models\Lyric;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

    /**
     * Create or update lyrics for a song (artist-scoped).
     */
    public function store(Request $request, string $songId): JsonResponse
    {
        $artist = auth()->user()->artist;
        if (!$artist) {
            return response()->json(['message' => 'Not an artist'], 403);
        }

        $song = Song::where('artist_id', $artist->id)->findOrFail($songId);

        $data = $request->validate([
            'content' => 'nullable|string',
            'synced_lyrics' => 'nullable|array',
            'synced_lyrics.*.time' => 'required_with:synced_lyrics|numeric',
            'synced_lyrics.*.text' => 'required_with:synced_lyrics|string',
            'language' => 'nullable|string|max:10',
            'source' => 'nullable|string|max:50',
        ]);

        $lyric = Lyric::updateOrCreate(
            ['song_id' => $song->id],
            [
                'content' => $data['content'] ?? null,
                'synced_lyrics' => $data['synced_lyrics'] ?? null,
                'language' => $data['language'] ?? null,
                'source' => $data['source'] ?? 'manual',
            ]
        );

        return response()->json($lyric);
    }
}

