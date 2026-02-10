<?php 
namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Album;
use App\Models\Artist;
use App\Models\ContentReport;
use App\Models\Genre;
use App\Models\Playlist;
use App\Models\PlaylistItem;
use App\Models\Podcast;
use App\Models\PodcastEpisode;
use App\Models\Song;
use App\Models\SongAiMetadata;
use App\Models\StreamHistory;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $password = Hash::make('password');

        $adminUser = User::create([
            'id' => Str::uuid(),
            'email' => 'admin@spotify.com',
            'username' => 'admin',
            'password_hash' => $password,
            'full_name' => 'Super Admin',
            'profile_image_url' => 'https://picsum.photos/seed/admin/300/300',
            'is_active' => true,
        ]);

        Admin::create(['user_id' => $adminUser->id]);

        $artistsData = [
            ['name' => 'Tulus', 'slug' => 'tulus', 'bio' => 'Penyanyi dan penulis lagu asal Indonesia.', 'listeners' => 4500000],
            ['name' => 'Pamungkas', 'slug' => 'pamungkas', 'bio' => 'Musisi indie asal Indonesia.', 'listeners' => 3200000],
            ['name' => 'Hindia', 'slug' => 'hindia', 'bio' => 'Penyanyi asal Jakarta.', 'listeners' => 2800000],
            ['name' => 'NIKI', 'slug' => 'niki', 'bio' => 'Penyanyi R&B internasional.', 'listeners' => 5000000],
            ['name' => 'Rich Brian', 'slug' => 'rich-brian', 'bio' => 'Rapper internasional.', 'listeners' => 4800000],
        ];

        $artists = [];
        foreach ($artistsData as $aData) {
            $artistUser = User::create([
                'id' => Str::uuid(),
                'email' => $aData['slug'] . '@spotify.com',
                'username' => $aData['slug'],
                'password_hash' => $password,
                'full_name' => $aData['name'],
                'is_active' => true,
            ]);

            $artists[] = Artist::create([
                'user_id' => $artistUser->id,
                'name' => $aData['name'],
                'slug' => $aData['slug'],
                'bio' => $aData['bio'],
                'avatar_url' => 'https://picsum.photos/seed/' . $aData['slug'] . '/300/300',
                'monthly_listeners' => $aData['listeners'],
                'is_verified' => true,
            ]);
        }

        $regularUser = User::create([
            'id' => Str::uuid(),
            'email' => 'user@spotify.com',
            'username' => 'musiclover',
            'password_hash' => $password,
            'full_name' => 'Regular User',
            'is_active' => true,
        ]);

        $genresData = ['Pop', 'Rock', 'R&B', 'Hip-Hop', 'Indie', 'Acoustic'];
        $genres = [];
        foreach ($genresData as $g) {
            $genres[] = Genre::create([
                'name' => $g,
                'slug' => Str::slug($g),
            ]);
        }

        $albums = [];
        foreach ($artists as $index => $artist) {
            $albums[] = Album::create([
                'artist_id' => $artist->id,
                'title' => 'Album ' . ($index + 1),
                'release_date' => now()->subYears(1),
                'cover_image_url' => 'https://picsum.photos/seed/album' . $index . '/300/300',
                'type' => 'ALBUM',
                'total_tracks' => 10,
            ]);
        }

        $songs = [];
        foreach ($albums as $album) {
            for ($i = 1; $i <= 2; $i++) {
                $title = "Song {$i} from {$album->title}";
                $songs[] = Song::create([
                    'id' => Str::uuid(),
                    'album_id' => $album->id,
                    'artist_id' => $album->artist_id,
                    'title' => $title,
                    'slug' => Str::slug($title) . '-' . Str::random(5),
                    'duration_seconds' => fake()->numberBetween(180, 300),
                    'file_path' => 'songs/private/' . Str::random(20) . '.mp3',
                    'file_size' => fake()->numberBetween(3000000, 9000000),
                    'cover_url' => $album->cover_image_url,
                    'stream_count' => fake()->numberBetween(1000, 1000000),
                ]);
            }
        }

        foreach ($songs as $song) {
            DB::table('song_genres')->insert([
                'song_id' => $song->id,
                'genre_id' => $genres[array_rand($genres)]->id,
            ]);

            SongAiMetadata::create([
                'song_id' => $song->id,
                'bpm' => fake()->numberBetween(70, 160),
                'mood_tags' => fake()->randomElements(['happy', 'sad', 'energetic', 'calm'], 2),
                'energy_score' => fake()->randomFloat(2, 0, 1),
            ]);
        }

        $playlist = Playlist::create([
            'user_id' => $regularUser->id,
            'name' => 'My Best Hits',
            'description' => 'Favorite songs',
            'is_public' => true,
        ]);

        foreach (collect($songs)->take(5) as $index => $song) {
            PlaylistItem::create([
                'playlist_id' => $playlist->id,
                'song_id' => $song->id,
                'position' => $index + 1,
            ]);
        }
    }
}