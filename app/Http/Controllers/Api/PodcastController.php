<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Podcast;
use App\Models\PodcastEpisode;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PodcastController extends Controller
{
    protected $cloudinary;

    public function __construct(CloudinaryService $cloudinary)
    {
        $this->cloudinary = $cloudinary;
    }

    public function index(): JsonResponse
    {
        $podcasts = Podcast::select(['id', 'artist_id', 'title', 'cover_image_url', 'category'])
            ->with(['artist:id,name'])
            ->paginate(20);

        return response()->json($podcasts);
    }

    public function show($id): JsonResponse
    {
        $podcast = Podcast::with([
            'episodes' => function ($q) {
            $q->select(['id', 'podcast_id', 'title', 'audio_url', 'duration_ms', 'release_date'])
                ->orderBy('release_date', 'desc');
        }
        ])->findOrFail($id);

        return response()->json($podcast);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|string|max:100',
            'artist_id' => 'required|uuid|exists:artists,id',
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        if ($request->hasFile('cover_image')) {
            $data['cover_image_url'] = $this->cloudinary->uploadImage(
                $request->file('cover_image'),
                'covers/podcasts'
            );
        }

        unset($data['cover_image']);

        $podcast = Podcast::create($data);
        return response()->json($podcast, 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $podcast = Podcast::findOrFail($id);

        $data = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'sometimes|required|string|max:100',
            'is_completed' => 'boolean',
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        if ($request->hasFile('cover_image')) {
            if ($podcast->cover_image_url) {
                $publicId = $this->cloudinary->getPublicIdFromUrl($podcast->cover_image_url);
                if ($publicId) {
                    $this->cloudinary->delete($publicId);
                }
            }

            $data['cover_image_url'] = $this->cloudinary->uploadImage(
                $request->file('cover_image'),
                'covers/podcasts'
            );
        }

        unset($data['cover_image']);

        $podcast->update($data);
        return response()->json($podcast);
    }

    public function destroy(string $id): JsonResponse
    {
        $podcast = Podcast::findOrFail($id);

        if ($podcast->cover_image_url) {
            $publicId = $this->cloudinary->getPublicIdFromUrl($podcast->cover_image_url);
            if ($publicId) {
                $this->cloudinary->delete($publicId);
            }
        }

        $podcast->delete();
        return response()->json(null, 204);
    }
}
