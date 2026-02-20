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
            'slug' => $this->slug,
            'cover_url' => $this->cover_url,
            'duration_seconds' => (int) $this->duration_seconds,
            'file_url' => $this->file_path,
            'stream_count' => (int) $this->stream_count,
            'is_explicit' => (bool) $this->is_explicit,
            'artist' => new ArtistResource($this->whenLoaded('artist')),
            'album' => [
                'id' => $this->album_id,
                'title' => $this->when($this->relationLoaded('album'), fn() => $this->album?->title),
                'cover_url' => $this->when($this->relationLoaded('album'), fn() => $this->album?->cover_image_url),
            ],
            'ai_metadata' => $this->whenLoaded('aiMetadata'),
            'lyrics' => $this->whenLoaded('lyric', fn() => $this->lyric?->content),
            'liked_at' => $this->whenPivotLoaded('liked_songs', fn() => $this->pivot->liked_at),
        ];
    }
}