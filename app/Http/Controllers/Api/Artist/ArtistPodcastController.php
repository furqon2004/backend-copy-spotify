<?php

namespace App\Http\Controllers\Api\Artist;

use App\Http\Controllers\Controller;
use App\Models\Podcast;
use App\Services\CloudinaryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ArtistPodcastController extends Controller
{
    protected $cloudinary;

    public function __construct(CloudinaryService $cloudinary)
    {
        $this->cloudinary = $cloudinary;
    }

    public function index(): JsonResponse
    {
        $artist = auth()->user()->artist;
        if (!$artist) {
            return response()->json(['message' => 'Not an artist'], 403);
        }

        $podcasts = Podcast::where('artist_id', $artist->id)
            ->withCount('episodes')
            ->orderByDesc('updated_at')
            ->paginate(20);

        return response()->json($podcasts);
    }

    public function store(Request $request): JsonResponse
    {
        $artist = auth()->user()->artist;
        if (!$artist) {
            return response()->json(['message' => 'Not an artist'], 403);
        }

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|string|max:100',
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        if ($request->hasFile('cover_image')) {
            $data['cover_image_url'] = $this->cloudinary->uploadImage(
                $request->file('cover_image'),
                'covers/podcasts'
            );
        }
        unset($data['cover_image']);

        $data['artist_id'] = $artist->id;
        $podcast = Podcast::create($data);

        return response()->json($podcast->loadCount('episodes'), 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $artist = auth()->user()->artist;
        if (!$artist) {
            return response()->json(['message' => 'Not an artist'], 403);
        }

        $podcast = Podcast::where('artist_id', $artist->id)->findOrFail($id);

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

        return response()->json($podcast->loadCount('episodes'));
    }

    public function destroy(string $id): JsonResponse
    {
        $artist = auth()->user()->artist;
        if (!$artist) {
            return response()->json(['message' => 'Not an artist'], 403);
        }

        $podcast = Podcast::where('artist_id', $artist->id)->findOrFail($id);

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
