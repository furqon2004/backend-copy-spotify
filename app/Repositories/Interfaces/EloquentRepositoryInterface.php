<?php 
namespace App\Repositories\Interfaces;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

interface EloquentRepositoryInterface
{
    public function findById(string $id, array $columns = ['*'], array $relations = []): ?Model;
    public function all(array $columns = ['*'], array $relations = []): Collection;
    public function create(array $data): Model;
    public function update(string $id, array $data): bool;
    public function delete(string $id): bool;
}