<?php
namespace App\Services;

use App\Models\Song;
use App\Models\Playlist;
use App\Models\PlaylistItem;
use App\Models\AiPlaylistUsage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SearchService
{
    /**
     * Check if user has reached daily AI playlist limit.
     */
    public function hasReachedDailyLimit(string $userId): bool
    {
        return AiPlaylistUsage::hasUsedToday($userId);
    }

    /**
     * Get remaining usage info for user.
     */
    public function getRemainingUsage(string $userId): array
    {
        $usedToday = AiPlaylistUsage::hasUsedToday($userId);

        return [
            'remaining' => $usedToday ? 0 : 1,
            'limit' => 1,
            'used_today' => $usedToday,
            'next_reset_at' => now()->addDay()->startOfDay()->toIso8601String(),
        ];
    }

    public function semanticSearch(string $prompt)
    {
        $cacheKey = 'lyric_search_' . md5(strtolower($prompt));

        return Cache::remember($cacheKey, now()->addHours(12), function () use ($prompt) {
            try {
                $response = Http::timeout(15)->withHeaders(['Content-Type' => 'application/json'])
                    ->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-lite:generateContent?key=" . config('services.gemini.key'), [
                        'contents' => [['parts' => [['text' => $this->buildLyricPrompt($prompt)]]]],
                        'generationConfig' => ['response_mime_type' => 'application/json']
                    ]);

                if ($response->failed())
                    return collect();

                $json = $response->json();
                $text = $json['candidates'][0]['content']['parts'][0]['text'] ?? null;
                if (!$text)
                    return collect();

                $data = json_decode($text, true);
                $keywords = $data['keywords'] ?? [];

                if (empty($keywords))
                    return collect();

                return Song::query()
                    ->where('status', 'APPROVED')
                    ->where(function ($q) use ($keywords) {
                        foreach ($keywords as $word) {
                            $q->orWhere('title', 'LIKE', "%{$word}%");
                        }
                    })
                    ->with(['artist', 'album'])
                    ->limit(20)
                    ->get();
            } catch (\Exception $e) {
                Log::warning('Semantic search failed: ' . $e->getMessage());
                return collect();
            }
        });
    }

    /**
     * Generate playlist from prompt using lyric/title-based matching.
     *
     * @param string $userId
     * @param string $prompt
     * @param bool   $force If true, skip missing song confirmation and create playlist anyway
     * @return array Returns either a confirmation response or the created playlist
     */
    public function generatePlaylistFromPrompt(string $userId, string $prompt, bool $force = false)
    {
        // Step 1: Gather all songs with their titles and lyrics for AI analysis
        $songsData = $this->gatherSongDataForAi();

        if (empty($songsData)) {
            Log::warning('No songs in database for AI playlist generation');
            return ['type' => 'error', 'message' => 'Tidak ada lagu di database'];
        }

        // Step 2: Send to Gemini for smart matching
        $aiResult = $this->askAiToMatchSongs($prompt, $songsData);

        if (!$aiResult) {
            Log::warning('AI matching failed, using fallback');
            $aiResult = [
                'matched_titles' => [],
                'missing_songs' => [],
                'playlist_name' => 'AI: ' . Str::title($prompt),
            ];
        }

        // Step 3: Find matched songs in DB
        $matchedTitles = $aiResult['matched_titles'] ?? [];
        $missingSongs = $aiResult['missing_songs'] ?? [];
        $playlistName = $aiResult['playlist_name'] ?? ('AI: ' . Str::title($prompt));

        $matchedSongs = collect();
        if (!empty($matchedTitles)) {
            $matchedSongs = Song::query()
                ->where('status', 'APPROVED')
                ->where(function ($q) use ($matchedTitles) {
                    foreach ($matchedTitles as $title) {
                        $q->orWhere('title', 'LIKE', '%' . trim($title) . '%');
                    }
                })
                ->with(['artist', 'album'])
                ->limit(20)
                ->get();
        }

        $minSongs = 8;

        // Step 4: If no matched songs, try fallback with keywords
        if ($matchedSongs->isEmpty()) {
            Log::info('No matched songs found, trying keyword fallback');
            $matchedSongs = $this->fallbackSearch($prompt);
        }

        // Step 5: Top up with random songs if below minimum
        if ($matchedSongs->count() < $minSongs) {
            $existingIds = $matchedSongs->pluck('id')->toArray();
            $needed = $minSongs - $matchedSongs->count();
            Log::info("Topping up playlist: have {$matchedSongs->count()}, need {$needed} more");

            $extraSongs = Song::where('status', 'APPROVED')
                ->whereNotIn('id', $existingIds)
                ->inRandomOrder()
                ->with(['artist', 'album'])
                ->limit($needed)
                ->get();

            $matchedSongs = $matchedSongs->concat($extraSongs);
        }

        if ($matchedSongs->isEmpty()) {
            return ['type' => 'error', 'message' => 'Tidak ada lagu yang bisa ditemukan'];
        }

        // Step 6: Create the playlist
        $playlist = DB::transaction(function () use ($userId, $matchedSongs, $playlistName, $prompt) {
            $playlist = Playlist::create([
                'id' => Str::uuid(),
                'user_id' => $userId,
                'name' => $playlistName,
                'description' => 'Curated by Gemini AI based on: ' . $prompt,
                'is_ai_generated' => true,
                'is_public' => true,
            ]);

            foreach ($matchedSongs as $index => $song) {
                PlaylistItem::create([
                    'playlist_id' => $playlist->id,
                    'song_id' => $song->id,
                    'position' => $index + 1,
                ]);
            }

            return $playlist->load('songs.artist');
        });

        // Step 7: Record daily usage
        AiPlaylistUsage::recordUsage($userId, $prompt);

        return [
            'type' => 'playlist_created',
            'playlist' => $playlist,
            'matched_count' => $matchedSongs->count(),
            'missing_songs' => $missingSongs,
        ];
    }

    /**
     * Gather song titles and lyrics for AI analysis.
     */
    private function gatherSongDataForAi(): array
    {
        $songs = Song::query()
            ->where('status', 'APPROVED')
            ->select(['id', 'title', 'artist_id'])
            ->with([
                'artist:id,name',
                'lyric:id,song_id,content',
                'genres:id,name',
            ])
            ->limit(500) // Limit to avoid token overflow
            ->get();

        return $songs->map(function ($song) {
            $entry = [
                'title' => $song->title,
                'artist' => $song->artist->name ?? 'Unknown',
                'genres' => $song->genres->pluck('name')->implode(', '),
            ];

            // Include a snippet of lyrics if available (max 100 chars to save tokens)
            if ($song->lyric && $song->lyric->content) {
                $entry['lyric_snippet'] = Str::limit(strip_tags($song->lyric->content), 100);
            }

            return $entry;
        })->toArray();
    }

    /**
     * Ask Gemini AI to match songs from our library based on the prompt.
     */
    private function askAiToMatchSongs(string $prompt, array $songsData): ?array
    {
        try {
            $songListText = collect($songsData)->map(function ($s, $i) {
                $line = ($i + 1) . '. "' . $s['title'] . '" by ' . $s['artist'];
                if (!empty($s['genres'])) {
                    $line .= ' [' . $s['genres'] . ']';
                }
                if (!empty($s['lyric_snippet'])) {
                    $line .= ' — Lirik: "' . $s['lyric_snippet'] . '"';
                }
                return $line;
            })->implode("\n");

            $aiPrompt = $this->buildSmartPlaylistPrompt($prompt, $songListText);

            $response = Http::timeout(30)->withHeaders(['Content-Type' => 'application/json'])
                ->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-lite:generateContent?key=" . config('services.gemini.key'), [
                    'contents' => [['parts' => [['text' => $aiPrompt]]]],
                    'generationConfig' => ['response_mime_type' => 'application/json'],
                ]);

            if ($response->failed()) {
                Log::error('Gemini API failed: ' . $response->status());
                return null;
            }

            $json = $response->json();
            $text = $json['candidates'][0]['content']['parts'][0]['text'] ?? null;

            if (!$text) {
                return null;
            }

            $data = json_decode($text, true);
            return $data;
        } catch (\Exception $e) {
            Log::warning('AI song matching failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * AI Smart Search: auto-detect query type and search accordingly.
     */
    public function aiSmartSearch(string $query): array
    {
        $cacheKey = 'ai_smart_search_' . md5(strtolower(trim($query)));

        return Cache::remember($cacheKey, now()->addHours(6), function () use ($query) {
            try {
                // Step 1: Classify the query type
                $classification = $this->classifyQuery($query);
                $queryType = $classification['type'] ?? 'title';
                $keywords = $classification['keywords'] ?? [];
                $aiReason = $classification['reason'] ?? '';

                // Step 2: Search based on type
                $songs = collect();

                switch ($queryType) {
                    case 'mood':
                        $songs = $this->searchByMood($query);
                        break;

                    case 'lyric':
                        $songs = $this->searchByLyric($query, $keywords);
                        break;

                    case 'title':
                    default:
                        $songs = Song::where('status', 'APPROVED')
                            ->where(function ($q) use ($query) {
                                $q->where('title', 'LIKE', "%{$query}%")
                                  ->orWhereHas('artist', fn($aq) => $aq->where('name', 'LIKE', "%{$query}%"));
                            })
                            ->with(['artist', 'album', 'aiMetadata'])
                            ->limit(20)
                            ->get();
                        $queryType = 'title';
                        if (empty($aiReason)) {
                            $aiReason = 'Menampilkan hasil pencarian berdasarkan judul lagu atau nama artis.';
                        }
                        break;
                }

                return [
                    'query_type' => $queryType,
                    'ai_reason' => $aiReason,
                    'songs' => $songs,
                    'total' => $songs->count(),
                ];
            } catch (\Exception $e) {
                Log::warning('AI Smart Search failed: ' . $e->getMessage());

                // Fallback to normal title search
                $songs = Song::where('status', 'APPROVED')
                    ->where(function ($q) use ($query) {
                        $q->where('title', 'LIKE', "%{$query}%")
                          ->orWhereHas('artist', fn($aq) => $aq->where('name', 'LIKE', "%{$query}%"));
                    })
                    ->with(['artist', 'album'])
                    ->limit(20)
                    ->get();

                return [
                    'query_type' => 'title',
                    'ai_reason' => 'Pencarian AI gagal, menampilkan hasil pencarian biasa.',
                    'songs' => $songs,
                    'total' => $songs->count(),
                ];
            }
        });
    }

    /**
     * Use Gemini to classify query as mood, lyric, or title.
     */
    private function classifyQuery(string $query): array
    {
        $prompt = $this->buildClassificationPrompt($query);

        $response = Http::timeout(10)->withHeaders(['Content-Type' => 'application/json'])
            ->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-lite:generateContent?key=" . config('services.gemini.key'), [
                'contents' => [['parts' => [['text' => $prompt]]]],
                'generationConfig' => ['response_mime_type' => 'application/json'],
            ]);

        if ($response->failed()) {
            return ['type' => 'title', 'keywords' => [], 'reason' => ''];
        }

        $json = $response->json();
        $text = $json['candidates'][0]['content']['parts'][0]['text'] ?? null;

        if (!$text) {
            return ['type' => 'title', 'keywords' => [], 'reason' => ''];
        }

        $data = json_decode($text, true);
        return $data ?? ['type' => 'title', 'keywords' => [], 'reason' => ''];
    }

    /**
     * AI-powered mood search: send all songs data to Gemini for matching.
     */
    private function searchByMood(string $query): \Illuminate\Support\Collection
    {
        $songsData = $this->gatherSongDataForAi();

        if (empty($songsData)) {
            return collect();
        }

        $songListText = collect($songsData)->map(function ($s, $i) {
            $line = ($i + 1) . '. "' . $s['title'] . '" by ' . $s['artist'];
            if (!empty($s['genres'])) {
                $line .= ' [' . $s['genres'] . ']';
            }
            if (!empty($s['lyric_snippet'])) {
                $line .= ' — Lirik: "' . $s['lyric_snippet'] . '"';
            }
            return $line;
        })->implode("\n");

        $aiPrompt = "Kamu adalah kurator musik AI. User mencari lagu dengan deskripsi: \"$query\"

Berikut daftar lagu yang tersedia:

$songListText

TUGAS:
1. Pilih lagu-lagu yang paling COCOK dengan deskripsi \"$query\" berdasarkan judul, genre, lirik, dan mood/suasana.
2. Urutkan dari yang paling relevan.
3. Pilih minimal 8 lagu dan maksimal 12 lagu.
4. USAHAKAN pilih 10 lagu yang paling cocok.
5. matched_titles HARUS berisi judul lagu yang PERSIS SAMA seperti di daftar di atas.

Return ONLY JSON:
{
  \"matched_titles\": [\"judul lagu persis dari daftar\"],
  \"reason\": \"penjelasan singkat kenapa lagu-lagu ini cocok dengan deskripsi user\"
}";

        $minSongs = 8;

        try {
            $response = Http::timeout(30)->withHeaders(['Content-Type' => 'application/json'])
                ->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-lite:generateContent?key=" . config('services.gemini.key'), [
                    'contents' => [['parts' => [['text' => $aiPrompt]]]],
                    'generationConfig' => ['response_mime_type' => 'application/json'],
                ]);

            if ($response->failed()) {
                return $this->fallbackSearch($query);
            }

            $json = $response->json();
            $text = $json['candidates'][0]['content']['parts'][0]['text'] ?? null;

            if (!$text) {
                return $this->fallbackSearch($query);
            }

            $data = json_decode($text, true);
            $matchedTitles = $data['matched_titles'] ?? [];

            $songs = collect();

            if (!empty($matchedTitles)) {
                $songs = Song::query()
                    ->where('status', 'APPROVED')
                    ->where(function ($q) use ($matchedTitles) {
                        foreach ($matchedTitles as $title) {
                            $q->orWhere('title', 'LIKE', '%' . trim($title) . '%');
                        }
                    })
                    ->with(['artist', 'album', 'aiMetadata'])
                    ->limit(20)
                    ->get();
            }

            // Top up with random songs if below minimum
            if ($songs->count() < $minSongs) {
                $existingIds = $songs->pluck('id')->toArray();
                $needed = $minSongs - $songs->count();

                $extraSongs = Song::where('status', 'APPROVED')
                    ->whereNotIn('id', $existingIds)
                    ->inRandomOrder()
                    ->with(['artist', 'album', 'aiMetadata'])
                    ->limit($needed)
                    ->get();

                $songs = $songs->concat($extraSongs);
            }

            return $songs;
        } catch (\Exception $e) {
            Log::warning('Mood search failed: ' . $e->getMessage());
            return $this->fallbackSearch($query);
        }
    }

    /**
     * Search songs by matching lyric content in the database.
     */
    private function searchByLyric(string $query, array $keywords = []): \Illuminate\Support\Collection
    {
        $searchTerms = !empty($keywords) ? $keywords : explode(' ', $query);

        // Search in lyrics content
        $songIds = \App\Models\Lyric::query()
            ->whereHas('song', fn($q) => $q->where('status', 'APPROVED'))
            ->where(function ($q) use ($searchTerms, $query) {
                // Try exact phrase match first
                $q->where('content', 'LIKE', '%' . $query . '%');

                // Also try individual keyword matches
                foreach ($searchTerms as $term) {
                    if (strlen($term) >= 3) {
                        $q->orWhere('content', 'LIKE', '%' . $term . '%');
                    }
                }
            })
            ->pluck('song_id');

        if ($songIds->isEmpty()) {
            // Fallback: also try title search
            return Song::where('status', 'APPROVED')
                ->where('title', 'LIKE', "%{$query}%")
                ->with(['artist', 'album', 'aiMetadata', 'lyric'])
                ->limit(10)
                ->get();
        }

        return Song::where('status', 'APPROVED')
            ->whereIn('id', $songIds)
            ->with(['artist', 'album', 'aiMetadata', 'lyric'])
            ->limit(20)
            ->get();
    }

    /**
     * Fallback search using keywords from the prompt.
     */
    private function fallbackSearch(string $prompt): \Illuminate\Support\Collection
    {
        $words = explode(' ', $prompt);

        return Song::query()
            ->where('status', 'APPROVED')
            ->where(function ($q) use ($words) {
                foreach ($words as $word) {
                    if (strlen($word) >= 3) {
                        $q->orWhere('title', 'LIKE', '%' . $word . '%');
                    }
                }
            })
            ->with(['artist'])
            ->inRandomOrder()
            ->limit(10)
            ->get();
    }

    private function buildClassificationPrompt(string $query): string
    {
        return "Analisis query pencarian musik berikut: \"$query\"

Tentukan tipe pencarian:
- \"mood\" — jika user mendeskripsikan suasana, aktivitas, atau konteks (contoh: \"lagu enak untuk hujan\", \"lagu sedih malam hari\", \"musik santai untuk kerja\", \"lagu semangat pagi\")
- \"lyric\" — jika user mengetik kutipan lirik lagu atau potongan kata-kata dari lagu (contoh: \"terlalu banyak yang ku mau\", \"we will we will rock you\", \"cause baby you're a firework\")
- \"title\" — jika user mengetik judul lagu atau nama artis secara langsung (contoh: \"Bohemian Rhapsody\", \"Tulus\", \"Adele Hello\")

Berikan juga:
- keywords: kata kunci penting dari query untuk pencarian
- reason: penjelasan singkat dalam Bahasa Indonesia kenapa query diklasifikasi ke tipe tersebut

Return ONLY JSON:
{
  \"type\": \"mood|lyric|title\",
  \"keywords\": [\"keyword1\", \"keyword2\"],
  \"reason\": \"penjelasan singkat\"
}";
    }

    private function buildLyricPrompt(string $query): string
    {
        return "Identify the main lyrical themes or keywords for: '$query'. 
        Return ONLY JSON: {'keywords': ['word1', 'word2']}.";
    }

    private function buildSmartPlaylistPrompt(string $prompt, string $songList): string
    {
        return "Kamu adalah kurator musik AI. User meminta playlist dengan tema: \"$prompt\"

Berikut adalah daftar lagu yang tersedia di perpustakaan kami beserta artis, genre, dan cuplikan liriknya:

$songList

TUGAS:
1. Baca judul, genre, dan lirik setiap lagu di atas dengan cermat.
2. Pilih lagu-lagu yang COCOK dengan tema \"$prompt\" berdasarkan:
   - Judul lagu yang relevan dengan tema
   - Genre yang sesuai
   - Lirik yang menggambarkan tema tersebut
   - Mood/suasana lagu
3. Jika ada lagu terkenal yang SEHARUSNYA cocok dengan tema ini tapi TIDAK ADA di daftar, sebutkan di missing_songs.
4. Berikan nama playlist yang menarik dan sesuai tema.

PENTING:
- matched_titles HARUS berisi judul lagu yang PERSIS SAMA seperti di daftar di atas
- missing_songs berisi judul lagu terkenal yang cocok tapi tidak ada di perpustakaan
- Pilih minimal 8 lagu dan maksimal 12 lagu
- USAHAKAN pilih 10 lagu yang paling cocok

Return ONLY JSON dengan format:
{
  \"matched_titles\": [\"judul lagu persis dari daftar\"],
  \"missing_songs\": [\"judul lagu yang tidak ada di daftar\"],
  \"playlist_name\": \"nama playlist yang menarik\"
}";
    }

    public function validatePrompt(string $prompt): array
    {
        try {
            $response = Http::timeout(10)->withHeaders(['Content-Type' => 'application/json'])
                ->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-lite:generateContent?key=" . config('services.gemini.key'), [
                    'contents' => [['parts' => [['text' => $this->buildValidationPrompt($prompt)]]]],
                    'generationConfig' => ['response_mime_type' => 'application/json']
                ]);

            if ($response->failed()) {
                return ['valid' => true]; // Jika AI gagal, izinkan request
            }

            $json = $response->json();
            $text = $json['candidates'][0]['content']['parts'][0]['text'] ?? null;

            if (!$text) {
                return ['valid' => true];
            }

            $data = json_decode($text, true);
            return $data ?? ['valid' => true];
        } catch (\Exception $e) {
            Log::warning('Prompt validation failed: ' . $e->getMessage());
            return ['valid' => true]; // Jika error, izinkan request
        }
    }

    private function buildValidationPrompt(string $query): string
    {
        return "Analyze if this prompt is valid for music playlist generation: '$query'

Valid prompts are:
- Music-related (songs, playlists, genres, moods)
- Specific enough (describes mood, genre, activity, or vibe)
- Examples: 'upbeat workout songs', 'sad romantic ballads', 'chill jazz for studying'

Invalid prompts are:
- Too vague: 'music', 'songs', 'good'
- Off-topic: 'how to cook', 'weather', 'programming tips'
- Nonsensical: 'asdfgh', random characters

Return JSON with this structure:
{
  \"valid\": true/false,
  \"reason\": \"brief explanation why it's invalid (empty if valid)\",
  \"examples\": [\"example 1\", \"example 2\", \"example 3\"] (only if invalid, suggest 3 similar valid prompts)
}

Return ONLY JSON.";
    }
}