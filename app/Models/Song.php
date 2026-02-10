<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Song extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'album_id',
        'artist_id',
        'title',
        'duration_ms',
        'file_url',
        'track_number',
        'stream_count',
        'lyrics'
    ];

    public function album()
    {
        return $this->belongsTo(Album::class);
    }
    public function artist()
    {
        return $this->belongsTo(Artist::class);
    }
    public function genres()
    {
        return $this->belongsToMany(Genre::class, 'song_genres');
    }
    public function aiMetadata()
    {
        return $this->hasOne(SongAiMetadata::class, 'song_id');
    }
}
