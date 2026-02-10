<?php

namespace App\Services;

use App\Models\Artist;
use App\Repositories\Interfaces\ArtistRepositoryInterface;
use App\Services\CloudinaryService;
use Illuminate\Support\Facades\DB;

class ArtistService
{
    protected $artistRepo;
    protected $cloudinary;

    public function __construct(ArtistRepositoryInterface $artistRepo, CloudinaryService $cloudinary)
    {
        $this->artistRepo = $artistRepo;
        $this->cloudinary = $cloudinary;
    }

    public function registerAsArtist(string $userId, array $data)
    {
        return DB::transaction(function () use ($userId, $data) {
            $avatarUrl = null;

            if (isset($data['avatar'])) {
                $avatarUrl = $this->cloudinary->uploadImage($data['avatar'], 'avatars/artists');
            }

            return Artist::create([
                'user_id' => $userId,
                'name' => $data['name'],
                'slug' => $data['slug'],
                'bio' => $data['bio'] ?? null,
                'avatar_url' => $avatarUrl,
                'is_verified' => false,
                'monthly_listeners' => 0
            ]);
        });
    }
}