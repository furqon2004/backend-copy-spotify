<?php

namespace App\Repositories;

use App\Models\Song;
use App\Repositories\Interfaces\EloquentRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class SongRepository implements EloquentRepositoryInterface
{
    protected $model;

    public function __construct(Song $model)
    {
        $this->model = $model;
    }

    public function findById(string $id, array $columns = ['*'], array $relations = []): ?Model
    {
        return $this->model->select($columns)->with($relations)->find($id);
    }

    public function all(array $columns = ['*'], array $relations = []): Collection
    {
        return $this->model->select($columns)->with($relations)->get();
    }

    public function getPopularSongs(int $limit = 10)
    {
        return $this->model->select(['id', 'album_id', 'artist_id', 'title', 'stream_count'])
            ->with([
                'artist:id,name,slug',
                'album:id,title,cover_image_url'
            ])
            ->orderBy('stream_count', 'desc')
            ->limit($limit)
            ->get();
    }

    public function streamLargeDataset()
    {
        return $this->model->select(['id', 'title', 'file_url'])
            ->lazy(500);
    }
}