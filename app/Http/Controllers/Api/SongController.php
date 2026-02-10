<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\SongRepository;
use Illuminate\Http\JsonResponse;

class SongController extends Controller
{
    protected $songRepo;

    public function __construct(SongRepository $songRepo)
    {
        $this->songRepo = $songRepo;
    }

    public function popular(): JsonResponse
    {
        $songs = $this->songRepo->getPopularSongs(20);
        return response()->json($songs);
    }

    public function show(string $id): JsonResponse
    {
        $song = $this->songRepo->findById(
            $id,
            ['id', 'album_id', 'artist_id', 'title', 'duration_ms', 'file_url', 'lyrics'],
            ['artist:id,name', 'album:id,title,cover_image_url', 'aiMetadata']
        );

        return response()->json($song);
    }
}
