<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SongAiMetadata;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SongAiMetadataController extends Controller
{
    public function show(string $songId): JsonResponse
    {
        $metadata = SongAiMetadata::where('song_id', $songId)->firstOrFail();

        return response()->json($metadata);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'song_id' => 'required|uuid|exists:songs,id|unique:song_ai_metadata,song_id',
            'bpm' => 'nullable|numeric|min:0|max:300',
            'key_signature' => 'nullable|string|max:10',
            'mood_tags' => 'nullable|array',
            'mood_tags.*' => 'string',
            'danceability' => 'nullable|numeric|min:0|max:1',
            'energy' => 'nullable|numeric|min:0|max:1',
            'valence' => 'nullable|numeric|min:0|max:1',
        ]);

        $metadata = SongAiMetadata::create($data);

        return response()->json($metadata, 201);
    }

    public function update(Request $request, string $songId): JsonResponse
    {
        $metadata = SongAiMetadata::where('song_id', $songId)->firstOrFail();

        $data = $request->validate([
            'bpm' => 'nullable|numeric|min:0|max:300',
            'key_signature' => 'nullable|string|max:10',
            'mood_tags' => 'nullable|array',
            'mood_tags.*' => 'string',
            'danceability' => 'nullable|numeric|min:0|max:1',
            'energy' => 'nullable|numeric|min:0|max:1',
            'valence' => 'nullable|numeric|min:0|max:1',
        ]);

        $metadata->update($data);

        return response()->json($metadata);
    }

    public function destroy(string $songId): JsonResponse
    {
        $metadata = SongAiMetadata::where('song_id', $songId)->firstOrFail();
        $metadata->delete();

        return response()->json(null, 204);
    }
}
