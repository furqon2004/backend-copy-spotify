<?php
namespace App\Services;

use App\Models\Song;
use App\Repositories\Interfaces\SongRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class ArtistSongService
{
    protected $songRepo;

    public function __construct(SongRepositoryInterface $songRepo)
    {
        $this->songRepo = $songRepo;
    }

    public function uploadSong(int $artistId, array $data)
    {
        return DB::transaction(function () use ($artistId, $data) {
            $coverUrl = Cloudinary::upload($data['cover']->getRealPath(), [
                'folder' => 'spotify_clone/covers'
            ])->getSecurePath();

            $audioPath = $data['audio']->store('songs/private', 'local');

            return $this->songRepo->create(array_merge($data, [
                'artist_id' => $artistId,
                'cover_url' => $coverUrl,
                'file_path' => $audioPath
            ]));
        });
    }

    public function updateSong(int $id, int $artistId, array $data)
    {
        $song = $this->songRepo->findOwnedByArtist($id, $artistId);
        
        return DB::transaction(function () use ($song, $data) {
            if (isset($data['cover'])) {
                $coverUrl = Cloudinary::upload($data['cover']->getRealPath())->getSecurePath();
                $data['cover_url'] = $coverUrl;
            }
            
            return $this->songRepo->update($song->id, $data);
        });
    }

    public function deleteSong(int $id, int $artistId)
    {
        $song = $this->songRepo->findOwnedByArtist($id, $artistId);
        
        DB::transaction(function () use ($song) {
            Storage::disk('local')->delete($song->file_path);
            $this->songRepo->delete($song->id);
        });
    }
}