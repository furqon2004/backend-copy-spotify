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

        if (!$query) {
            return response()->json([]);
        }

        if ($request->has('ai') && $request->ai == 'true') {
            $songs = $this->searchService->semanticSearch($query);
            return response()->json([
                'songs' => SongResource::collection($songs),
                'artists' => [],
                'playlists' => [],
                'podcasts' => [],
                'genres' => [],
            ]);
        }

        // 1. Songs
        $songs = Song::where('title', 'LIKE', "%{$query}%")
            ->orWhereHas('artist', fn($q) => $q->where('name', 'LIKE', "%{$query}%"))
            ->with(['artist:id,name,slug', 'album:id,title,cover_image_url'])
            ->limit(10)
            ->get();

        // 2. Artists
        $artists = \App\Models\Artist::where('name', 'LIKE', "%{$query}%")
            ->limit(10)
            ->get();

        // 3. Playlists (Public only)
        $playlists = \App\Models\Playlist::where('name', 'LIKE', "%{$query}%")
            ->where('is_public', true)
            ->limit(10)
            ->get();

        // 4. Podcasts
        $podcasts = \App\Models\Podcast::where('title', 'LIKE', "%{$query}%")
            ->limit(10)
            ->get();

        // 5. Genres
        $genres = \App\Models\Genre::where('name', 'LIKE', "%{$query}%")
            ->limit(10)
            ->get();

        return response()->json([
            'songs' => SongResource::collection($songs),
            'artists' => $artists,
            'playlists' => $playlists,
            'podcasts' => $podcasts,
            'genres' => $genres,
        ]);
    }

    public function generatePlaylist(Request $request)
    {
        $request->validate(['prompt' => 'required|string|min:3']);

        // Validate prompt dengan AI
        $validation = $this->searchService->validatePrompt($request->prompt);

        if (!$validation['valid']) {
            return response()->json([
                'message' => 'Prompt tidak valid untuk generate playlist',
                'error' => $validation['reason'] ?? 'Prompt terlalu vague atau tidak berhubungan dengan musik',
                'examples' => $validation['examples'] ?? [
                    'lagu upbeat untuk workout',
                    'lagu sad romantic untuk patah hati',
                    'lagu chill jazz untuk belajar',
                    'EDM energik untuk party'
                ],
                'suggestion' => 'Gunakan prompt yang lebih spesifik dan berhubungan dengan musik'
            ], 422);
        }

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