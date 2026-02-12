<?php 
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SearchService;
use Illuminate\Http\Request;
use App\Http\Resources\SongResource;
use App\Models\Song;

class SearchController extends Controller
{
    protected $searchService;

    public function __construct(SearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    public function index(Request $request)
    {
        $query = $request->query('q');

        if ($request->has('ai') && $request->ai == 'true') {
            $songs = $this->searchService->semanticSearch($query);
            return SongResource::collection($songs);
        }

        $songs = Song::where('title', 'LIKE', "%{$query}%")
            ->orWhereHas('artist', fn($q) => $q->where('name', 'LIKE', "%{$query}%"))
            ->with(['artist:id,name', 'album:id,title,cover_image_url'])
            ->paginate(20);

        return SongResource::collection($songs);
    }

    public function generatePlaylist(Request $request)
    {
        $request->validate(['prompt' => 'required|string|min:3']);

        $playlist = $this->searchService->generatePlaylistFromPrompt(
            auth()->id(), 
            $request->prompt
        );

        if (!$playlist) {
            return response()->json(['message' => 'Could not find enough songs for this prompt'], 404);
        }

        return response()->json([
            'message' => 'AI has curated your playlist with ' . $playlist->songs->count() . ' songs!',
            'data' => $playlist
        ], 201);
    }
}