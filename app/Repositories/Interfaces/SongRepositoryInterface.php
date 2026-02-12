<?php 
namespace App\Repositories\Interfaces;

use App\Models\Song;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Repositories\Interfaces\EloquentRepositoryInterface;

interface SongRepositoryInterface extends EloquentRepositoryInterface
{
public function paginateByArtist(string $artistId, int $perPage = 20): LengthAwarePaginator;
public function findOwnedByArtist(string $id, string $artistId): Song;
public function create(array $data): Song;
public function update(string $id, array $data): bool;
public function delete(string $id): bool;
}