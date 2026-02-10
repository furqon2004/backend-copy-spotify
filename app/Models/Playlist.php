<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Playlist extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'cover_url',
        'is_ai_generated',
        'ai_prompt_used',
        'is_public'
    ];

    protected $casts = [
        'is_ai_generated' => 'boolean',
        'is_public' => 'boolean'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function songs()
    {
        return $this->belongsToMany(Song::class, 'playlist_items')
            ->withPivot('position', 'added_at');
    }
}
