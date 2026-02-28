<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Genre extends Model
{
    use HasUuids;
    protected $fillable = ['name', 'slug', 'color', 'image_url'];

    public function songs()
    {
        return $this->belongsToMany(Song::class, 'song_genres');
    }
}
