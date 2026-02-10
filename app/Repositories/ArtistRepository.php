<?php

namespace App\Repositories;

use App\Models\Artist;
use App\Repositories\Interfaces\ArtistRepositoryInterface;
use Illuminate\Support\Facades\DB;

class ArtistRepository implements ArtistRepositoryInterface
{
    protected $model;

    public function __construct(Artist $model)
    {
        $this->model = $model;
    }

    public function findBySlug(string $slug)
    {
        return $this->model->select(['id', 'name', 'slug', 'bio', 'avatar_url', 'is_verified', 'monthly_listeners'])
            ->withCount(['albums', 'songs'])
            ->where('slug', $slug)
            ->firstOrFail();
    }

    public function getTopArtists(int $limit = 10)
    {
        return $this->model->select(['id', 'name', 'slug', 'avatar_url', 'monthly_listeners'])
            ->where('is_verified', true)
            ->orderBy('monthly_listeners', 'desc')
            ->limit($limit)
            ->get();
    }

    public function updateMonthlyListeners(string $artistId)
    {
        $count = DB::table('stream_history')
            ->where('played_at', '>=', now()->subDays(30))
            ->whereHas('song', function ($q) use ($artistId) {
                $q->where('artist_id', $artistId);
            })
            ->distinct('user_id')
            ->count('user_id');

        return $this->model->where('id', $artistId)->update(['monthly_listeners' => $count]);
    }
}