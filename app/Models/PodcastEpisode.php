<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PodcastEpisode extends Model
{
    use HasUuids;

    protected $fillable = ['podcast_id', 'title', 'description', 'audio_url', 'duration_ms', 'stream_count', 'release_date'];

    public function podcast()
    {
        return $this->belongsTo(Podcast::class);
    }
}