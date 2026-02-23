<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
class Song extends Model
{
    use HasUuids;

    protected $fillable = [
        'album_id',
        'artist_id',
        'title',
        'slug',
        'cover_url',
        'file_path',
        'file_size',
        'duration_seconds',
        'stream_count',
        'is_explicit',
        'status',
        'moderation_note',
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
    public function lyric()
    {
        return $this->hasOne(Lyric::class);
    }
}
