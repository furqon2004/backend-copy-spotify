<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Podcast extends Model
{
use HasUuids;

protected $fillable = ['artist_id', 'title', 'description', 'cover_image_url', 'category', 'is_completed'];

public function artist() { return $this->belongsTo(Artist::class); }
public function episodes() { return $this->hasMany(PodcastEpisode::class); }
}