<?php
namespace App\Services;

use App\Repositories\Interfaces\SongRepositoryInterface;
use Illuminate\Support\Facades\DB;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Support\Str;

class ArtistSongService
{
    protected $songRepo;

    public function __construct(SongRepositoryInterface $songRepo)
    {
        $this->songRepo = $songRepo;
    }

    public function uploadSong(string $artistId, array $data)
    {
        return DB::transaction(function () use ($artistId, $data) {
            // Audio MP3 diupload ke Cloudinary
            $audioUpload = Cloudinary::upload($data['audio']->getRealPath(), [
                'folder' => 'spotify_clone/songs',
                'resource_type' => 'video',
                'chunk_size' => 6000000 
            ]);

            $song = $this->songRepo->create([
                'id' => Str::uuid(),
                'artist_id' => $artistId,
                'album_id' => $data['album_id'] ?? null,
                'title' => $data['title'],
                'slug' => Str::slug($data['title']) . '-' . Str::random(5),
                'cover_url' => $data['cover_url'] ?? null,
                'file_path' => $audioUpload->getSecurePath(),
                'file_size' => $data['audio']->getSize(),
                'duration_seconds' => (int) $audioUpload->getDuration(),
            ]);

            // Create lyric record if lyrics provided (optional)
            if (!empty($data['lyrics'])) {
                $song->lyric()->create([
                    'content' => $data['lyrics'],
                    'source'  => 'manual',
                ]);
            }

            return $song->load('lyric');
        });
    }

    public function updateSong(string $id, string $artistId, array $data)
    {
        $song = $this->songRepo->findOwnedByArtist($id, $artistId);
        
        return DB::transaction(function () use ($song, $data) {

            if (isset($data['audio'])) {
                $audioUpload = Cloudinary::upload($data['audio']->getRealPath(), [
                    'folder' => 'spotify_clone/songs',
                    'resource_type' => 'video',
                    'chunk_size' => 6000000
                ]);
                $data['file_path'] = $audioUpload->getSecurePath();
                $data['file_size'] = $data['audio']->getSize();
                $data['duration_seconds'] = (int) $audioUpload->getDuration();
            }

            // Handle lyrics: create/update/delete
            if (array_key_exists('lyrics', $data)) {
                if (!empty($data['lyrics'])) {
                    $song->lyric()->updateOrCreate(
                        ['song_id' => $song->id],
                        ['content' => $data['lyrics'], 'source' => 'manual']
                    );
                } else {
                    $song->lyric()->delete();
                }
            }

            // Remove non-column keys before updating song
            unset($data['lyrics'], $data['audio'], $data['genre_ids']);

            if (!empty($data)) {
                $this->songRepo->update($song->id, $data);
            }

            return $song->fresh(['lyric']);
        });
    }

    public function deleteSong(string $id, string $artistId)
    {
        $song = $this->songRepo->findOwnedByArtist($id, $artistId);
        
        DB::transaction(function () use ($song) {
            $publicId = 'spotify_clone/songs/' . pathinfo($song->file_path, PATHINFO_FILENAME);
            Cloudinary::destroy($publicId, ['resource_type' => 'video']);
            
            $this->songRepo->delete($song->id);
        });
    }
}