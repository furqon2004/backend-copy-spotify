<?php 
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PlaylistItem extends Model
{
    use HasUuids;

    protected $table = 'playlist_items';

    protected $fillable = [
        'playlist_id',
        'song_id',
        'position',
    ];

    protected $casts = [
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