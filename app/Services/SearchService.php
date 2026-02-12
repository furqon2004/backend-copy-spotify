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

                if ($response->failed()) return collect();

                $json = $response->json();
                $text = $json['candidates'][0]['content']['parts'][0]['text'] ?? null;
                if (!$text) return collect();

                $data = json_decode($text, true);
                $keywords = $data['keywords'] ?? [];

                if (empty($keywords)) return collect();

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

            if ($response->failed()) return null;

            $json = $response->json();
            $text = $json['candidates'][0]['content']['parts'][0]['text'] ?? null;
            if (!$text) return null;

            $data = json_decode($text, true);
            if (!$data) return null;
        } catch (\Exception $e) {
            Log::warning('AI playlist generation failed: ' . $e->getMessage());
            return null;
        }

        return DB::transaction(function () use ($userId, $data, $prompt) {
            $songs = Song::query()
                ->when(!empty($data['genres']), fn($q) => $q->whereHas('genres', fn($g) => $g->whereIn('name', $data['genres'])))
                ->when(!empty($data['moods']), fn($q) => $q->whereHas('aiMetadata', fn($m) => $m->whereJsonContains('mood_tags', $data['moods'])))
                ->inRandomOrder()
                ->limit(rand(8, 10))
                ->get();

            if ($songs->isEmpty()) return null;

            $playlist = Playlist::create([
                'id' => Str::uuid(),
                'user_id' => $userId,
                'name' => 'AI: ' . Str::title($prompt),
                'description' => 'Curated by Gemini AI based on: ' . $prompt,
                'is_ai_generated' => true,
                'is_public' => true
            ]);

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
}