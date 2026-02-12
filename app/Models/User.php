<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasUuids;
 
    protected $fillable = [
        'email',
        'username',
        'password_hash',
        'full_name',
        'profile_image_url',
        'date_of_birth',
        'gender',
        'last_login_at',
        'is_active'
    ];

    protected $hidden = ['password_hash', 'remember_token'];

    protected $casts = [
        'last_login_at' => 'datetime',
        'is_active' => 'boolean',
        'date_of_birth' => 'date'
    ];

    public function admin()
    {
        return $this->hasOne(Admin::class);
    }
    public function artist()
    {
        return $this->hasOne(Artist::class);
    }
    public function playlists()
    {
        return $this->hasMany(Playlist::class);
    }
    public function likedSongs()
    {
        return $this->belongsToMany(Song::class, 'liked_songs')->withPivot('liked_at');
    }
}