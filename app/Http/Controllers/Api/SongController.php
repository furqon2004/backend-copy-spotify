<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SongStoreRequest;
use App\Services\ArtistSongService;
use App\Repositories\SongRepository;
use Illuminate\Http\JsonResponse;

class SongController extends Controller
{
    protected $songService;
    protected $songRepo;

    public function __construct(ArtistSongService $songService, SongRepository $songRepo)
    {
        $this->songService = $songService;
        $this->songRepo = $songRepo;
    }

    public function index(): JsonResponse
    {
        $songs = $this->songRepo->paginateByArtist(auth()->user()->artist->id);
        return response()->json($songs);
    }

    public function store(SongStoreRequest $request): JsonResponse
    {
        $song = $this->songService->uploadSong(auth()->user()->artist->id, $request->validated());
        return response()->json($song, 201);
    }

    public function show(string $id): JsonResponse
    {
        $song = $this->songRepo->findOwnedByArtist($id, auth()->user()->artist->id);
        return response()->json($song);
    }

    public function update(SongStoreRequest $request, string $id): JsonResponse
    {
        $song = $this->songService->updateSong($id, auth()->user()->artist->id, $request->validated());
        return response()->json($song);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->songService->deleteSong($id, auth()->user()->artist->id);
        return response()->json(null, 204);
    }
}