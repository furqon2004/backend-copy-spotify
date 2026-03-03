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
        $request->validate(['q' => 'required|string|min:2']);

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

    /**
     * AI Search: menampilkan lagu berdasarkan deskripsi/mood (GET, tanpa membuat playlist).
     */
    public function aiSearch(Request $request)
    {
        $request->validate(['q' => 'required|string|min:3']);

        $query = $request->query('q');

        $aiResult = $this->searchService->aiSmartSearch($query);

        return response()->json([
            'query_type' => $aiResult['query_type'],
            'ai_reason' => $aiResult['ai_reason'],
            'songs' => SongResource::collection($aiResult['songs']),
            'total' => $aiResult['total'],
        ]);
    }

    /**
     * AI Playlist: buat playlist otomatis berdasarkan deskripsi/mood menggunakan AI.
     * Limit: 3x per hari per user.
     */
    public function aiPlaylist(Request $request)
    {
        $request->validate([
            'prompt' => 'required|string|min:2|max:200',
        ]);

        $userId = $request->user()->id;
        $prompt = $request->input('prompt');

        // Cek limit harian (3x per hari)
        $usage = $this->searchService->getRemainingUsage($userId);
        if ($usage['remaining'] <= 0) {
            return response()->json([
                'message' => 'Kamu sudah mencapai batas pembuatan AI playlist hari ini (3x/hari).',
                'usage' => $usage,
            ], 429);
        }

        // Validasi prompt
        $validation = $this->searchService->validatePrompt($prompt);
        if (!($validation['valid'] ?? true)) {
            return response()->json([
                'message' => $validation['reason'] ?? 'Prompt tidak valid untuk pembuatan playlist.',
                'examples' => $validation['examples'] ?? [],
            ], 422);
        }

        // Generate playlist
        $result = $this->searchService->generatePlaylistFromPrompt($userId, $prompt);

        if ($result['type'] === 'error') {
            return response()->json([
                'message' => $result['message'],
            ], 404);
        }

        return response()->json([
            'message' => 'Playlist berhasil dibuat!',
            'playlist' => $result['playlist'],
            'matched_count' => $result['matched_count'],
            'missing_songs' => $result['missing_songs'] ?? [],
            'usage' => $this->searchService->getRemainingUsage($userId),
        ], 201);
    }

}