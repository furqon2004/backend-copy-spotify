<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SearchService;
use Illuminate\Http\Request;
use App\Http\Resources\SongResource;

class SearchController extends Controller
{
    protected $searchService;

    public function __construct(SearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    public function index(Request $request)
    {
        $request->validate(['q' => 'required|string|min:1']);

        $query = $request->query('q');
        $type = $request->query('type'); // null (default) | 'mood'

        // ── Mood search: menggunakan AI (Gemini) ──────────────────────
        if ($type === 'mood') {
            $aiResult = $this->searchService->aiSmartSearch($query);

            return response()->json([
                'query_type' => $aiResult['query_type'],
                'ai_reason' => $aiResult['ai_reason'],
                'songs' => SongResource::collection($aiResult['songs']),
                'artists' => [],
                'playlists' => [],
                'podcasts' => [],
                'genres' => [],
            ]);
        }

        // ── Default search: langsung dari database (hemat token) ─────

        // Songs: cari berdasarkan judul ATAU lirik ATAU nama artis
        $songs = \App\Models\Song::where('status', 'APPROVED')
            ->where(function ($q) use ($query) {
                $q->where('title', 'LIKE', "%{$query}%")
                  ->orWhereHas('artist', fn($aq) => $aq->where('name', 'LIKE', "%{$query}%"))
                  ->orWhereHas('lyric', fn($lq) => $lq->where('content', 'LIKE', "%{$query}%"));
            })
            ->with(['artist:id,name,slug', 'album:id,title,cover_image_url'])
            ->limit(20)
            ->get();

        // Artists
        $artists = \App\Models\Artist::where('name', 'LIKE', "%{$query}%")
            ->limit(10)
            ->get();

        // Playlists (Public only)
        $playlists = \App\Models\Playlist::where('name', 'LIKE', "%{$query}%")
            ->where('is_public', true)
            ->limit(10)
            ->get();

        // Podcasts
        $podcasts = \App\Models\Podcast::where('title', 'LIKE', "%{$query}%")
            ->limit(10)
            ->get();

        // Genres
        $genres = \App\Models\Genre::where('name', 'LIKE', "%{$query}%")
            ->limit(10)
            ->get();

        return response()->json([
            'query_type' => 'default',
            'ai_reason' => null,
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

        // Check daily limit (1x per day)
        if ($this->searchService->hasReachedDailyLimit(auth()->id())) {
            return response()->json([
                'message' => 'Anda sudah menggunakan AI playlist hari ini. Coba lagi besok.',
                'next_available_at' => now()->addDay()->startOfDay()->toIso8601String(),
            ], 429);
        }

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

        $force = $request->boolean('force', false);

        $result = $this->searchService->generatePlaylistFromPrompt(
            auth()->id(),
            $request->prompt,
            $force
        );

        if (!$result) {
            return response()->json(['message' => 'Could not find enough songs for this prompt'], 404);
        }

        // Handle different result types
        $type = $result['type'] ?? 'unknown';

        if ($type === 'error') {
            return response()->json(['message' => $result['message']], 404);
        }

        if ($type === 'playlist_created') {
            $playlist = $result['playlist'];
            $response = [
                'message' => 'AI telah membuat playlist dengan ' . $playlist->songs->count() . ' lagu!',
                'data' => $playlist,
            ];

            if (!empty($result['missing_songs'])) {
                $response['missing_songs'] = $result['missing_songs'];
                $response['note'] = 'Beberapa lagu yang disarankan tidak tersedia di perpustakaan kami';
            }

            return response()->json($response, 201);
        }

        return response()->json(['message' => 'Unexpected error'], 500);
    }

    /**
     * Check remaining AI playlist usage for today.
     */
    public function checkRemainingUsage()
    {
        return response()->json(
            $this->searchService->getRemainingUsage(auth()->id())
        );
    }
}