<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SongResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'duration_ms' => (int) $this->duration_ms,
            'file_url' => $this->file_url,
            'stream_count' => (int) $this->stream_count,
            'track_number' => (int) $this->track_number,
            'lyrics' => $this->lyrics,
            'artist' => new ArtistResource($this->whenLoaded('artist')),
            'album' => [
                'id' => $this->album_id,
                'title' => $this->when($this->relationLoaded('album'), fn() => $this->album->title),
                'cover_url' => $this->when($this->relationLoaded('album'), fn() => $this->album->cover_image_url),
            ],
            'ai_metadata' => $this->whenLoaded('aiMetadata'),
            'liked_at' => $this->whenPivotLoaded('liked_songs', fn() => $this->pivot->liked_at),
        ];
    }
}