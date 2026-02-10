<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Artist extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id', 'name', 'slug', 'bio', 'avatar_url', 
        'monthly_listeners', 'is_verified'
    ];

    public function user() { return $this->belongsTo(User::class); }
    public function albums() { return $this->hasMany(Album::class); }
    public function songs() { return $this->hasMany(Song::class); }
}