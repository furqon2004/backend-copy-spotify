<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StreamHistory extends Model
{
    protected $table = 'stream_history';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'song_id',
        'played_at',
        'duration_played_ms',
        'source',
        'device'
    ];

    protected $casts = ['played_at' => 'datetime'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function song()
    {
        return $this->belongsTo(Song::class);
    }
}
