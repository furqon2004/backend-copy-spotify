<?php 
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlaylistItem extends Model
{
    protected $table = 'playlist_items';
    
    public $timestamps = false;

    protected $fillable = [
        'playlist_id',
        'song_id',
        'position',
        'added_at'
    ];

    protected $casts = [
        'added_at' => 'datetime',
        'position' => 'integer'
    ];

    public function playlist()
    {
        return $this->belongsTo(Playlist::class);
    }

    public function song()
    {
        return $this->belongsTo(Song::class);
    }
}