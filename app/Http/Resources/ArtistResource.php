<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ArtistResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'bio' => $this->bio,
            'avatar_url' => $this->avatar_url,
            'monthly_listeners' => (int) $this->monthly_listeners,
            'is_verified' => (bool) $this->is_verified,
            'stats' => [
                'songs_count' => $this->whenCounted('songs'),
                'albums_count' => $this->whenCounted('albums'),
            ],
            'created_at' => $this->created_at,
        ];
    }
}
