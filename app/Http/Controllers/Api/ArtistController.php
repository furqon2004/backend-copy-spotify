<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ArtistRegistrationRequest;
use App\Services\ArtistService;
use App\Repositories\Interfaces\ArtistRepositoryInterface;
use Illuminate\Http\JsonResponse;

class ArtistController extends Controller
{
    protected $artistService;
    protected $artistRepo;

    public function __construct(ArtistService $artistService, ArtistRepositoryInterface $artistRepo)
    {
        $this->artistService = $artistService;
        $this->artistRepo = $artistRepo;
    }

    public function store(ArtistRegistrationRequest $request): JsonResponse
    {
        $artist = $this->artistService->registerAsArtist(auth()->id(), $request->validated());
        return response()->json($artist, 201);
    }

    public function show(string $slug): JsonResponse
    {
        $artist = $this->artistRepo->findBySlug($slug);
        return response()->json($artist);
    }
}