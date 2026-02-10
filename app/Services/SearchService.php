<?php
namespace App\Services;

use App\Models\Song;
use App\Models\Playlist;
use Illuminate\Support\Facades\Http;

class SearchService
{
    public function __construct()
    {
    }

    public function semanticSearch(string $prompt)
    {
        $embedding = $this->generateEmbedding($prompt);

        $songIds = $this->queryVectorDatabase($embedding);

        return Song::whereIn('id', $songIds)
            ->with(['artist:id,name', 'album:id,title,cover_image_url'])
            ->get();
    }

    public function generatePlaylistFromPrompt(string $userId, string $prompt)
    {
        $aiResponse = Http::withToken(config('services.openai.key'))
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a music expert. Return only JSON with: title, description, and filter_criteria (mood, genre, min_bpm).'],
                    ['role' => 'user', 'content' => "Create a playlist based on this vibe: $prompt"]
                ],
                'response_format' => ['type' => 'json_object']
            ])->json();

        $criteria = json_decode($aiResponse['choices'][0]['message']['content'], true);

        $songs = Song::whereHas('aiMetadata', function ($query) use ($criteria) {
            $query->whereIn('mood_tags', $criteria['filter_criteria']['mood'] ?? [])
                ->orWhere('bpm', '>=', $criteria['filter_criteria']['min_bpm'] ?? 0);
        })->limit(20)->pluck('id');

        $playlist = Playlist::create([
            'user_id' => $userId,
            'name' => $criteria['title'],
            'description' => $criteria['description'],
            'is_ai_generated' => true,
            'ai_prompt_used' => $prompt
        ]);

        $playlist->songs()->attach($songs);

        return $playlist;
    }

    private function generateEmbedding(string $text)
    {
        $response = Http::withToken(config('services.openai.key'))
            ->post('https://api.openai.com/v1/embeddings', [
                'model' => 'text-embedding-3-small',
                'input' => $text
            ]);

        return $response->json()['data'][0]['embedding'];
    }

    private function queryVectorDatabase(array $embedding)
    {
        $response = Http::withToken(config('services.qdrant.key'))
            ->post(config('services.qdrant.url') . '/collections/songs/points/search', [
                'vector' => $embedding,
                'limit' => 15,
                'with_payload' => true
            ]);

        return collect($response->json()['result'])->pluck('id')->toArray();
    }
}