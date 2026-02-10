<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Podcast;
use App\Models\PodcastEpisode;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;

class PodcastController extends Controller
{
    protected $cloudinary;

    public function __construct(CloudinaryService $cloudinary)
    {
        $this->cloudinary = $cloudinary;
    }

    public function index()
    {
        return Podcast::select(['id', 'artist_id', 'title', 'cover_image_url', 'category'])
            ->with(['artist:id,name'])
            ->paginate(20);
    }

    public function show($id)
    {
        return Podcast::with([
            'episodes' => function ($q) {
                $q->select(['id', 'podcast_id', 'title', 'audio_url', 'duration_ms', 'release_date'])
                    ->orderBy('release_date', 'desc');
            }
        ])->findOrFail($id);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required',
            'category' => 'required',
            'artist_id' => 'required|uuid',
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        // Handle cover image upload via Cloudinary
        if ($request->hasFile('cover_image')) {
            $data['cover_image_url'] = $this->cloudinary->uploadImage(
                $request->file('cover_image'),
                'covers/podcasts'
            );
        }

        unset($data['cover_image']);

        return Podcast::create($data);
    }
}
