<?php 
namespace App\Services;

use App\Models\Song;
use App\Models\Playlist;
use App\Models\PlaylistItem;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class SearchService
{
    public function semanticSearch(string $prompt)
    {
        $cacheKey = 'lyric_search_' . md5(strtolower($prompt));

        return Cache::remember($cacheKey, now()->addHours(12), function () use ($prompt) {
            $response = Http::withHeaders(['Content-Type' => 'application/json'])
                ->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . config('services.gemini.key'), [
                    'contents' => [['parts' => [['text' => $this->buildLyricPrompt($prompt)]]]],
                    'generationConfig' => ['response_mime_type' => 'application/json']
                ]);

            if ($response->failed()) return collect();

            $data = json_decode($response->json()['candidates'][0]['content']['parts'][0]['text'], true);
            $keywords = $data['keywords'] ?? [];

            return Song::query()
                ->where(function ($q) use ($keywords) {
                    foreach ($keywords as $word) {
                        $q->orWhere('lyrics', 'LIKE', "%{$word}%");
                    }
                })
                ->with(['artist', 'album'])
                ->limit(20)
                ->get();
        });
    }

    public function generatePlaylistFromPrompt(string $userId, string $prompt)
    {
        $response = Http::withHeaders(['Content-Type' => 'application/json'])
            ->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . config('services.gemini.key'), [
                'contents' => [['parts' => [['text' => $this->buildPlaylistPrompt($prompt)]]]],
                'generationConfig' => ['response_mime_type' => 'application/json']
            ]);

        $data = json_decode($response->json()['candidates'][0]['content']['parts'][0]['text'], true);

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