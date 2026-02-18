<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Album;
use App\Services\CloudinaryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AlbumController extends Controller
{
    protected $cloudinary;

    public function __construct(CloudinaryService $cloudinary)
    {
        $this->cloudinary = $cloudinary;
    }

    /**
     * List albums for the authenticated artist.
     */
    public function index(): JsonResponse
    {
        $artistId = auth()->user()->artist->id;

        $albums = Album::where('artist_id', $artistId)
            ->select(['id', 'title', 'cover_image_url', 'release_date', 'type', 'total_tracks', 'created_at'])
            ->withCount('songs')
            ->latest('release_date')
            ->paginate(20);

        return response()->json($albums);
    }

    /**
     * Show album detail with songs.
     */
    public function show(string $id): JsonResponse
    {
        $album = Album::with([
            'artist:id,name,slug',
            'songs' => function ($q) {
                $q->select(['id', 'album_id', 'artist_id', 'title', 'slug', 'cover_url', 'duration_seconds', 'stream_count', 'track_number'])
                    ->orderBy('track_number');
            },
        ])->findOrFail($id);

        return response()->json($album);
    }

    /**
     * Create a new album.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'release_date' => 'nullable|date',
            'type' => 'required|in:ALBUM,SINGLE,EP',
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $artistId = auth()->user()->artist->id;

        if ($request->hasFile('cover_image')) {
            $data['cover_image_url'] = $this->cloudinary->uploadImage(
                $request->file('cover_image'),
                'covers/albums'
            );
        }

        unset($data['cover_image']);

        $album = Album::create(array_merge($data, [
            'artist_id' => $artistId,
            'total_tracks' => 0,
        ]));

        return response()->json($album, 201);
    }

    /**
     * Update an album.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $artistId = auth()->user()->artist->id;
        $album = Album::where('artist_id', $artistId)->findOrFail($id);

        $data = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'release_date' => 'nullable|date',
            'type' => 'sometimes|required|in:ALBUM,SINGLE,EP',
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        if ($request->hasFile('cover_image')) {
            // Delete old cover
            if ($album->cover_image_url) {
                $publicId = $this->cloudinary->getPublicIdFromUrl($album->cover_image_url);
                if ($publicId) {
                    $this->cloudinary->delete($publicId);
                }
            }

            $data['cover_image_url'] = $this->cloudinary->uploadImage(
                $request->file('cover_image'),
                'covers/albums'
            );
        }

        unset($data['cover_image']);

        $album->update($data);
        return response()->json($album);
    }

    /**
     * Delete an album.
     */
    public function destroy(string $id): JsonResponse
    {
        $artistId = auth()->user()->artist->id;
        $album = Album::where('artist_id', $artistId)->findOrFail($id);

        if ($album->cover_image_url) {
            $publicId = $this->cloudinary->getPublicIdFromUrl($album->cover_image_url);
            if ($publicId) {
                $this->cloudinary->delete($publicId);
            }
        }

        $album->delete();
        return response()->json(null, 204);
    }
}
