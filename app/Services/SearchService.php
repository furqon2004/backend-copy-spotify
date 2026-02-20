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
                    ->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . config('services.gemini.key'), [
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
                ->where(function ($q) use ($matchedTitles) {
                    foreach ($matchedTitles as $title) {
                        $q->orWhere('title', 'LIKE', '%' . $title . '%');
                    }
                })
                ->with(['artist', 'album'])
                ->limit(20)
                ->get();
        }

        // Step 4: If there are missing songs and user hasn't confirmed, return confirmation
        if (!empty($missingSongs) && !$force) {
            return [
                'type' => 'confirmation_required',
                'requires_confirmation' => true,
                'message' => 'Beberapa lagu yang cocok tidak tersedia di perpustakaan kami',
                'matched_songs' => $matchedSongs->map(fn($s) => [
                    'id' => $s->id,
                    'title' => $s->title,
                    'artist' => $s->artist->name ?? null,
                ])->values(),
                'missing_songs' => $missingSongs,
                'matched_count' => $matchedSongs->count(),
                'missing_count' => count($missingSongs),
                'playlist_name' => $playlistName,
                'prompt' => $prompt,
            ];
        }

        // Step 5: If no matched songs at all, try fallback with keywords
        if ($matchedSongs->isEmpty()) {
            Log::info('No matched songs found, trying keyword fallback');
            $matchedSongs = $this->fallbackSearch($prompt);
        }

        // Step 6: Still nothing? Get random songs as last resort
        if ($matchedSongs->isEmpty()) {
            Log::info('Keyword fallback also empty, using random songs');
            $matchedSongs = Song::inRandomOrder()->with(['artist'])->limit(5)->get();
        }

        if ($matchedSongs->isEmpty()) {
            return ['type' => 'error', 'message' => 'Tidak ada lagu yang bisa ditemukan'];
        }

        // Step 7: Create the playlist
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

        // Step 8: Record daily usage
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
                ->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=" . config('services.gemini.key'), [
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
     * Fallback search using keywords from the prompt.
     */
    private function fallbackSearch(string $prompt): \Illuminate\Support\Collection
    {
        $words = explode(' ', $prompt);

        return Song::query()
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
- Pilih minimal 5 lagu jika memungkinkan, maksimal 15

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
                ->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . config('services.gemini.key'), [
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