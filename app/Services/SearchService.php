<?php
namespace App\Services;

use App\Models\Song;
use App\Models\Playlist;
use App\Models\PlaylistItem;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SearchService
{
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

    public function generatePlaylistFromPrompt(string $userId, string $prompt)
    {
        try {
            $response = Http::timeout(15)->withHeaders(['Content-Type' => 'application/json'])
                ->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . config('services.gemini.key'), [
                    'contents' => [['parts' => [['text' => $this->buildPlaylistPrompt($prompt)]]]],
                    'generationConfig' => ['response_mime_type' => 'application/json']
                ]);

            if ($response->failed()) {
                Log::error('Gemini API failed with status ' . $response->status());
                // return null; // Don't return, fallback below
            }

            $json = $response->json();
            $text = $json['candidates'][0]['content']['parts'][0]['text'] ?? null;

            if ($text) {
                $data = json_decode($text, true);
            }
        } catch (\Exception $e) {
            Log::warning('AI playlist generation failed: ' . $e->getMessage());
        }

        // Ensure data is an array even if API failed
        if (empty($data)) {
            $data = ['genres' => [], 'moods' => [$prompt]]; // Use prompt as mood/keyword fallback
        }

        return DB::transaction(function () use ($userId, $data, $prompt) {
            Log::info("Generating playlist for user $userId with prompt: $prompt");

            // 1. Try strict matching (Genre OR Mood)
            $query = Song::query();

            // Broaden search: Use OR instead of potential AND if multiple conditions
            $query->where(function ($q) use ($data) {
                if (!empty($data['genres'])) {
                    $q->orWhereHas('genres', fn($g) => $g->whereIn('name', $data['genres']));
                }
                if (!empty($data['moods'])) {
                    $q->orWhereHas('aiMetadata', fn($m) => $m->whereJsonContains('mood_tags', $data['moods']));
                }
                // Fallback: title match
                if (!empty($data['moods'][0])) {
                    $q->orWhere('title', 'LIKE', '%' . $data['moods'][0] . '%');
                }
            });

            $songs = $query->inRandomOrder()
                ->limit(rand(10, 15))
                ->get();

            Log::info("Found " . $songs->count() . " songs matching criteria.");

            // 2. If nothing found, just get random songs (Fallback for "demo" purposes or empty DB metadata)
            if ($songs->isEmpty()) {
                Log::info("No songs found, falling back to random.");
                $songs = Song::inRandomOrder()->limit(5)->get();
                Log::info("Fallback found " . $songs->count() . " songs.");
            }

            if ($songs->isEmpty()) {
                Log::error("Still no songs found. Aborting.");
                return null;
            }

            $playlist = Playlist::create([
                'id' => Str::uuid(),
                'user_id' => $userId,
                'name' => 'AI: ' . Str::title($prompt),
                'description' => 'Curated by Gemini AI based on: ' . $prompt,
                'is_ai_generated' => true,
                'is_public' => true
            ]);

            Log::info("Created playlist: " . $playlist->id);

            foreach ($songs as $index => $song) {
                PlaylistItem::create([
                    'playlist_id' => $playlist->id,
                    'song_id' => $song->id,
                    'position' => $index + 1
                ]);
            }

            return $playlist->load('songs.artist');
        });
    }

    private function buildLyricPrompt(string $query): string
    {
        return "Identify the main lyrical themes or keywords for: '$query'. 
        Return ONLY JSON: {'keywords': ['word1', 'word2']}.";
    }

    private function buildPlaylistPrompt(string $query): string
    {
        return "Analyze this request for a playlist: '$query'. 
        Extract as JSON: {'genres': [], 'moods': []}. 
        Return ONLY JSON.";
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