<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SongAiMetadata extends Model
{
    protected $table = 'song_ai_metadata';
    protected $primaryKey = 'song_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'song_id',
        'vector_id',
        'bpm',
        'key_signature',
        'mood_tags',
        'danceability',
        'energy',
        'valence',
        'last_analyzed_at'
    ];

    protected $casts = [
        'mood_tags' => 'array',
        'last_analyzed_at' => 'datetime',
        'bpm' => 'float',
        'danceability' => 'float',
        'energy' => 'float',
        'valence' => 'float'
    ];

    public function song()
    {
        return $this->belongsTo(Song::class, 'song_id');
    }
}