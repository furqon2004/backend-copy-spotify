<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PodcastEpisode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PodcastEpisodeController extends Controller
{
    public function index(string $podcastId): JsonResponse
    {
        $episodes = PodcastEpisode::where('podcast_id', $podcastId)
            ->orderByDesc('release_date')
            ->paginate(20);

        return response()->json($episodes);
    }

    public function store(Request $request, string $podcastId): JsonResponse
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'audio_url' => 'required|string|max:2048',
            'duration_ms' => 'required|integer|min:1',
            'release_date' => 'required|date',
        ]);

        $episode = PodcastEpisode::create(array_merge($data, [
            'podcast_id' => $podcastId,
        ]));

        return response()->json($episode, 201);
    }

    public function show(string $podcastId, string $episodeId): JsonResponse
    {
        $episode = PodcastEpisode::where('podcast_id', $podcastId)
            ->findOrFail($episodeId);

        return response()->json($episode);
    }

    public function update(Request $request, string $podcastId, string $episodeId): JsonResponse
    {
        $episode = PodcastEpisode::where('podcast_id', $podcastId)
            ->findOrFail($episodeId);

        $data = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'audio_url' => 'sometimes|required|string|max:2048',
            'duration_ms' => 'sometimes|required|integer|min:1',
            'release_date' => 'sometimes|required|date',
        ]);

        $episode->update($data);

        return response()->json($episode);
    }

    public function destroy(string $podcastId, string $episodeId): JsonResponse
    {
        $episode = PodcastEpisode::where('podcast_id', $podcastId)
            ->findOrFail($episodeId);

        $episode->delete();

        return response()->json(null, 204);
    }
}
