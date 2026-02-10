<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlaylistResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'cover_url' => $this->cover_url,
            'is_ai_generated' => (bool) $this->is_ai_generated,
            'ai_prompt_used' => $this->when($this->is_ai_generated, $this->ai_prompt_used),
            'is_public' => (bool) $this->is_public,
            'owner' => [
                'id' => $this->user_id,
                'name' => $this->when($this->relationLoaded('user'), fn() => $this->user->full_name),
            ],
            'songs' => SongResource::collection($this->whenLoaded('songs')),
            'songs_count' => $this->whenCounted('songs'),
            'created_at' => $this->created_at,
        ];
    }
}