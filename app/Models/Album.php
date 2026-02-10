<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Album extends Model
{
    use HasUuids;

    protected $fillable = [
        'artist_id',
        'title',
        'release_date',
        'cover_image_url',
        'type',
        'total_tracks'
    ];

    protected $casts = ['release_date' => 'date'];

    public function artist()
    {
        return $this->belongsTo(Artist::class);
    }
    public function songs()
    {
        return $this->hasMany(Song::class);
    }
}
