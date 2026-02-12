<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SongAiMetadata extends Model
{
    protected $table = 'song_ai_metadata';

    protected $fillable = [
        'song_id',
        'mood_tags',
        'bpm',
        'key_signature',
        'energy_score',
    ];

    protected $casts = [
        'mood_tags' => 'array',
        'bpm' => 'integer',
        'energy_score' => 'float',
    ];

    public function song()
    {
        return $this->belongsTo(Song::class, 'song_id');
    }
}