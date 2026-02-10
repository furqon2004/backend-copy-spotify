<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Genre extends Model
{
    public $timestamps = false;
    protected $fillable = ['name', 'slug'];

    public function songs()
    {
        return $this->belongsToMany(Song::class, 'song_genres');
    }
}
