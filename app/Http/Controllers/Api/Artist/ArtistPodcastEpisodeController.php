<?php

namespace App\Http\Controllers\Api\Artist;

use App\Http\Controllers\Controller;
use App\Models\Podcast;
use App\Models\PodcastEpisode;
use App\Services\CloudinaryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ArtistPodcastEpisodeController extends Controller
{
    protected $cloudinary;

    public function __construct(CloudinaryService $cloudinary)
    {
        $this->cloudinary = $cloudinary;
    }

    /**
     * Verify podcast belongs to the authenticated artist.
     */
    private function getArtistPodcast(string $podcastId): Podcast
    {
        $artist = auth()->user()->artist;
        abort_if(!$artist, 403, 'Not an artist');

        return Podcast::where('artist_id', $artist->id)->findOrFail($podcastId);
    }

    public function index(string $podcastId): JsonResponse
    {
        $this->getArtistPodcast($podcastId);

        $episodes = PodcastEpisode::where('podcast_id', $podcastId)
            ->orderByDesc('release_date')
            ->paginate(20);

        return response()->json($episodes);
    }

    public function store(Request $request, string $podcastId): JsonResponse
    {
        $this->getArtistPodcast($podcastId);

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'audio' => 'required|file|mimes:mp3,wav,ogg,m4a,aac|max:102400',
            'duration_ms' => 'required|integer|min:1',
            'release_date' => 'required|date',
        ]);

        // Upload audio to Cloudinary
        $data['audio_url'] = $this->cloudinary->uploadAudio(
            $request->file('audio'),
            'podcasts/episodes'
        );
        unset($data['audio']);

        $episode = PodcastEpisode::create(array_merge($data, [
            'podcast_id' => $podcastId,
        ]));

        return response()->json($episode, 201);
    }

    public function update(Request $request, string $podcastId, string $episodeId): JsonResponse
    {
        $this->getArtistPodcast($podcastId);

        $episode = PodcastEpisode::where('podcast_id', $podcastId)->findOrFail($episodeId);

        $data = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'audio' => 'nullable|file|mimes:mp3,wav,ogg,m4a,aac|max:102400',
            'duration_ms' => 'sometimes|required|integer|min:1',
            'release_date' => 'sometimes|required|date',
        ]);

        if ($request->hasFile('audio')) {
            // Delete old audio from Cloudinary
            if ($episode->audio_url) {
                $publicId = $this->cloudinary->getPublicIdFromUrl($episode->audio_url);
                if ($publicId) {
                    $this->cloudinary->delete($publicId, 'video');
                }
            }
            $data['audio_url'] = $this->cloudinary->uploadAudio(
                $request->file('audio'),
                'podcasts/episodes'
            );
        }
        unset($data['audio']);

        $episode->update($data);

        return response()->json($episode);
    }

    public function destroy(string $podcastId, string $episodeId): JsonResponse
    {
        $this->getArtistPodcast($podcastId);

        $episode = PodcastEpisode::where('podcast_id', $podcastId)->findOrFail($episodeId);
        $episode->delete();

        return response()->json(null, 204);
    }
}
