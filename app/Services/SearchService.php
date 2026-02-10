<?php 
namespace App\Services;

use App\Models\Song;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class SearchService
{
    public function searchWithAI(string $prompt)
    {
        $cacheKey = 'ai_search_' . md5(strtolower(trim($prompt)));

        return Cache::remember($cacheKey, now()->addHours(24), function () use ($prompt) {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . config('services.gemini.key'), [
                'contents' => [
                    ['parts' => [['text' => $this->buildPrompt($prompt)]]]
                ],
                'generationConfig' => [
                    'response_mime_type' => 'application/json',
                ]
            ]);

            if ($response->failed()) {
                return collect();
            }

            $data = json_decode($response->json()['candidates'][0]['content']['parts'][0]['text'], true);

            return Song::query()
                ->when(!empty($data['genres']), function ($q) use ($data) {
                    $q->whereHas('genres', fn($query) => $query->whereIn('name', $data['genres']));
                })
                ->when(!empty($data['moods']), function ($q) use ($data) {
                    $q->whereHas('aiMetadata', fn($query) => $query->whereJsonContains('mood_tags', $data['moods']));
                })
                ->when(isset($data['min_bpm']), function ($q) use ($data) {
                    $q->whereHas('aiMetadata', fn($query) => $query->where('bpm', '>=', $data['min_bpm']));
                })
                ->with(['artist', 'album'])
                ->limit(15)
                ->get();
        });
    }

    private function buildPrompt(string $userQuery): string
    {
        return "Analyze this music search query: '$userQuery'. 
        Extract as JSON: {'genres': [], 'moods': [], 'min_bpm': int/null}. 
        Return ONLY valid JSON.";
    }
}