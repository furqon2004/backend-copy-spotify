<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Lyric extends Model
{
    use HasUuids;

    protected $fillable = [
        'song_id',
        'content',
        'synced_lyrics',
        'language',
        'source',
    ];

    protected $casts = [
        'synced_lyrics' => 'array',
    ];

    public function song()
    {
        return $this->belongsTo(Song::class);
    }
}
