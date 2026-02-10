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

        // =============================================
        // 1. USERS — 10 total: 1 admin, 8 artists, 1 regular
        // =============================================
        $adminUser = User::create([
            'email' => 'admin@spotify.com',
            'username' => 'admin',
            'password_hash' => $password,
            'full_name' => 'Super Admin',
            'profile_image_url' => 'https://picsum.photos/seed/admin/300/300',
            'date_of_birth' => '1990-01-15',
            'gender' => 'Male',
            'is_active' => true,
        ]);

        Admin::create(['user_id' => $adminUser->id]);

        $artistsData = [
            ['name' => 'Tulus', 'slug' => 'tulus', 'bio' => 'Penyanyi dan penulis lagu asal Indonesia yang dikenal dengan suara falsetto khasnya.', 'listeners' => 4500000],
            ['name' => 'Pamungkas', 'slug' => 'pamungkas', 'bio' => 'Musisi indie asal Indonesia yang terkenal dengan lagu-lagu romantisnya.', 'listeners' => 3200000],
            ['name' => 'Hindia', 'slug' => 'hindia', 'bio' => 'Rapper dan penyanyi asal Jakarta, dikenal dengan lirik yang puitis.', 'listeners' => 2800000],
            ['name' => 'NIKI', 'slug' => 'niki', 'bio' => 'Penyanyi R&B asal Indonesia yang berkarir internasional di bawah label 88rising.', 'listeners' => 5000000],
            ['name' => 'Rich Brian', 'slug' => 'rich-brian', 'bio' => 'Rapper dan produser asal Indonesia yang sukses di kancah internasional.', 'listeners' => 4800000],
            ['name' => 'Raisa', 'slug' => 'raisa', 'bio' => 'Penyanyi pop Indonesia yang populer dengan suara merdu dan lagu-lagu hits.', 'listeners' => 3900000],
            ['name' => 'Isyana Sarasvati', 'slug' => 'isyana-sarasvati', 'bio' => 'Penyanyi dan pianis klasik Indonesia dengan talenta luar biasa.', 'listeners' => 2500000],
            ['name' => 'Fiersa Besari', 'slug' => 'fiersa-besari', 'bio' => 'Musisi, penulis, dan traveler asal Bandung. Dikenal dengan lagu-lagu akustiknya.', 'listeners' => 3100000],
        ];

        $artists = [];
        foreach ($artistsData as $aData) {
            $artistUser = User::create([
                'email' => $aData['slug'] . '@spotify.com',
                'username' => $aData['slug'],
                'password_hash' => $password,
                'full_name' => $aData['name'],
                'profile_image_url' => 'https://picsum.photos/seed/' . $aData['slug'] . '/300/300',
                'date_of_birth' => fake()->dateTimeBetween('-40 years', '-20 years')->format('Y-m-d'),
                'gender' => fake()->randomElement(['Male', 'Female']),
                'is_active' => true,
            ]);

            $artists[] = Artist::create([
                'user_id' => $artistUser->id,
                'name' => $aData['name'],
                'slug' => $aData['slug'],
                'bio' => $aData['bio'],
                'avatar_url' => 'https://picsum.photos/seed/' . $aData['slug'] . '-avatar/300/300',
                'monthly_listeners' => $aData['listeners'],
                'is_verified' => true,
            ]);
        }

        $regularUser = User::create([
            'email' => 'user@spotify.com',
            'username' => 'musiclover',
            'password_hash' => $password,
            'full_name' => 'Regular User',
            'profile_image_url' => 'https://picsum.photos/seed/regularuser/300/300',
            'date_of_birth' => '1998-05-20',
            'gender' => 'Female',
            'is_active' => true,
        ]);

        // =============================================
        // 2. GENRES — 10 genres
        // =============================================
        $genresData = ['Pop', 'Rock', 'R&B', 'Hip-Hop', 'Jazz', 'Electronic', 'Indie', 'Dangdut', 'Acoustic', 'Lo-fi'];
        $genres = [];
        foreach ($genresData as $g) {
            $genres[] = Genre::create([
                'name' => $g,
                'slug' => Str::slug($g),
            ]);
        }

        // =============================================
        // 3. ALBUMS — 10 albums across artists
        // =============================================
        $albumsData = [
            ['artist' => 0, 'title' => 'Manusia', 'type' => 'ALBUM', 'tracks' => 10],
            ['artist' => 0, 'title' => 'Monokrom', 'type' => 'ALBUM', 'tracks' => 8],
            ['artist' => 1, 'title' => 'Walk The Talk', 'type' => 'ALBUM', 'tracks' => 12],
            ['artist' => 2, 'title' => 'Menari Dengan Bayangan', 'type' => 'EP', 'tracks' => 5],
            ['artist' => 3, 'title' => 'Zephyr', 'type' => 'ALBUM', 'tracks' => 11],
            ['artist' => 4, 'title' => 'Amen', 'type' => 'ALBUM', 'tracks' => 13],
            ['artist' => 5, 'title' => 'Handmade', 'type' => 'ALBUM', 'tracks' => 10],
            ['artist' => 6, 'title' => 'Lexicon', 'type' => 'ALBUM', 'tracks' => 9],
            ['artist' => 7, 'title' => 'Garis Waktu', 'type' => 'ALBUM', 'tracks' => 7],
            ['artist' => 7, 'title' => 'Konspirasi Alam Semesta', 'type' => 'SINGLE', 'tracks' => 1],
        ];

        $albums = [];
        foreach ($albumsData as $alData) {
            $albums[] = Album::create([
                'artist_id' => $artists[$alData['artist']]->id,
                'title' => $alData['title'],
                'release_date' => fake()->dateTimeBetween('-5 years', 'now')->format('Y-m-d'),
                'cover_image_url' => 'https://picsum.photos/seed/' . Str::slug($alData['title']) . '/300/300',
                'type' => $alData['type'],
                'total_tracks' => $alData['tracks'],
            ]);
        }

        // =============================================
        // 4. SONGS — 10 songs across albums
        // =============================================
        $songsData = [
            ['album' => 0, 'artist' => 0, 'title' => 'Hati-Hati di Jalan', 'track' => 1, 'streams' => 8500000],
            ['album' => 0, 'artist' => 0, 'title' => 'Ruang Sendiri', 'track' => 2, 'streams' => 5200000],
            ['album' => 2, 'artist' => 1, 'title' => 'To The Bone', 'track' => 1, 'streams' => 9800000],
            ['album' => 3, 'artist' => 2, 'title' => 'Secukupnya', 'track' => 1, 'streams' => 4300000],
            ['album' => 4, 'artist' => 3, 'title' => 'Every Summertime', 'track' => 1, 'streams' => 12000000],
            ['album' => 5, 'artist' => 4, 'title' => 'Dat $tick', 'track' => 1, 'streams' => 15000000],
            ['album' => 6, 'artist' => 5, 'title' => 'Kali Kedua', 'track' => 1, 'streams' => 7600000],
            ['album' => 7, 'artist' => 6, 'title' => 'Tetap Dalam Jiwa', 'track' => 1, 'streams' => 6800000],
            ['album' => 8, 'artist' => 7, 'title' => 'Waktu Yang Salah', 'track' => 1, 'streams' => 11000000],
            ['album' => 1, 'artist' => 0, 'title' => 'Monokrom', 'track' => 1, 'streams' => 6000000],
        ];

        $songs = [];
        foreach ($songsData as $sData) {
            $songs[] = Song::create([
                'album_id' => $albums[$sData['album']]->id,
                'artist_id' => $artists[$sData['artist']]->id,
                'title' => $sData['title'],
                'duration_ms' => fake()->numberBetween(180000, 320000),
                'file_url' => 'https://res.cloudinary.com/demo/video/upload/sample.mp3',
                'track_number' => $sData['track'],
                'stream_count' => $sData['streams'],
                'lyrics' => "Ini adalah lirik contoh untuk lagu {$sData['title']}.\nBaris kedua dari lirik.\nBaris ketiga dari lirik.",
            ]);
        }

        // =============================================
        // 5. SONG_GENRES — Assign genres to songs
        // =============================================
        $songGenreMap = [
            0 => [0, 8],    // Hati-Hati di Jalan -> Pop, Acoustic
            1 => [0, 6],    // Ruang Sendiri -> Pop, Indie
            2 => [0, 6],    // To The Bone -> Pop, Indie
            3 => [6, 3],    // Secukupnya -> Indie, Hip-Hop
            4 => [2, 0],    // Every Summertime -> R&B, Pop
            5 => [3],       // Dat $tick -> Hip-Hop
            6 => [0, 2],    // Kali Kedua -> Pop, R&B
            7 => [0, 4],    // Tetap Dalam Jiwa -> Pop, Jazz
            8 => [0, 8],    // Waktu Yang Salah -> Pop, Acoustic
            9 => [0, 4],    // Monokrom -> Pop, Jazz
        ];

        foreach ($songGenreMap as $songIdx => $genreIdxs) {
            foreach ($genreIdxs as $genreIdx) {
                DB::table('song_genres')->insert([
                    'song_id' => $songs[$songIdx]->id,
                    'genre_id' => $genres[$genreIdx]->id,
                ]);
            }
        }

        // =============================================
        // 6. PLAYLISTS — 10 playlists
        // =============================================
        $allUsers = [$adminUser, $regularUser, ...array_map(fn($a) => User::find($a->user_id), $artists)];

        $playlistsData = [
            ['user' => $regularUser->id, 'name' => 'My Daily Mix', 'desc' => 'Lagu-lagu favorit sehari-hari'],
            ['user' => $regularUser->id, 'name' => 'Chill Vibes', 'desc' => 'Playlist untuk santai'],
            ['user' => $regularUser->id, 'name' => 'Workout Hits', 'desc' => 'Semangat olahraga!'],
            ['user' => $adminUser->id, 'name' => 'Admin Picks', 'desc' => 'Rekomendasi dari admin'],
            ['user' => $adminUser->id, 'name' => 'Top Indo 2026', 'desc' => 'Lagu Indonesia terpopuler 2026'],
            ['user' => $artists[0]->user_id, 'name' => 'Inspirasi Tulus', 'desc' => 'Lagu-lagu yang menginspirasi'],
            ['user' => $artists[1]->user_id, 'name' => 'Late Night Drive', 'desc' => 'Untuk perjalanan malam'],
            ['user' => $artists[3]->user_id, 'name' => 'R&B Essentials', 'desc' => 'Koleksi R&B terbaik'],
            ['user' => $artists[4]->user_id, 'name' => 'Hip-Hop Nation', 'desc' => 'Hip-Hop hits'],
            ['user' => $artists[7]->user_id, 'name' => 'Akustik Senja', 'desc' => 'Lagu akustik untuk sore hari'],
        ];

        $playlists = [];
        foreach ($playlistsData as $pData) {
            $playlists[] = Playlist::create([
                'user_id' => $pData['user'],
                'name' => $pData['name'],
                'description' => $pData['desc'],
                'cover_url' => 'https://picsum.photos/seed/' . Str::slug($pData['name']) . '/300/300',
                'is_ai_generated' => false,
                'is_public' => true,
            ]);
        }

        // =============================================
        // 7. PLAYLIST_ITEMS — Add songs to playlists
        // =============================================
        foreach ($playlists as $pl) {
            $shuffled = collect($songs)->shuffle()->take(rand(3, 7));
            $position = 1;
            foreach ($shuffled as $song) {
                PlaylistItem::create([
                    'playlist_id' => $pl->id,
                    'song_id' => $song->id,
                    'position' => $position++,
                    'added_at' => now()->subDays(rand(0, 30)),
                ]);
            }
        }

        // =============================================
        // 8. LIKED_SONGS — Users like some songs
        // =============================================
        $likingUsers = [$adminUser, $regularUser];
        foreach ($likingUsers as $lu) {
            $likedSongs = collect($songs)->shuffle()->take(rand(3, 7));
            foreach ($likedSongs as $ls) {
                DB::table('liked_songs')->insert([
                    'user_id' => $lu->id,
                    'song_id' => $ls->id,
                    'liked_at' => now()->subDays(rand(0, 60)),
                ]);
            }
        }

        // =============================================
        // 9. STREAM_HISTORY — 10 stream records
        // =============================================
        $sources = ['PLAYLIST', 'SEARCH', 'AI_RECOMMENDATION'];
        $devices = ['iPhone 15', 'Samsung Galaxy S24', 'MacBook Pro', 'Windows PC', 'iPad Air'];
        for ($i = 0; $i < 10; $i++) {
            StreamHistory::create([
                'user_id' => fake()->randomElement([$adminUser->id, $regularUser->id]),
                'song_id' => $songs[array_rand($songs)]->id,
                'played_at' => now()->subHours(rand(1, 720)),
                'duration_played_ms' => fake()->numberBetween(30000, 300000),
                'source' => fake()->randomElement($sources),
                'device' => fake()->randomElement($devices),
            ]);
        }

        // =============================================
        // 10. CONTENT_REPORTS — A few sample reports
        // =============================================
        ContentReport::create([
            'reporter_id' => $regularUser->id,
            'target_type' => 'SONG',
            'target_id' => $songs[5]->id,
            'reason' => 'Konten mengandung bahasa yang tidak pantas.',
            'status' => 'PENDING',
        ]);

        ContentReport::create([
            'reporter_id' => $regularUser->id,
            'target_type' => 'PLAYLIST',
            'target_id' => $playlists[5]->id,
            'reason' => 'Judul playlist menyesatkan.',
            'status' => 'RESOLVED',
        ]);

        // =============================================
        // 11. SONG_AI_METADATA — AI metadata for songs
        // =============================================
        $keys = ['C', 'C#', 'D', 'D#', 'E', 'F', 'F#', 'G', 'G#', 'A', 'A#', 'B'];
        $moods = ['happy', 'sad', 'energetic', 'calm', 'romantic', 'melancholic', 'uplifting', 'chill'];

        foreach ($songs as $song) {
            SongAiMetadata::create([
                'song_id' => $song->id,
                'vector_id' => 'vec_' . Str::random(12),
                'bpm' => fake()->randomFloat(1, 70, 180),
                'key_signature' => fake()->randomElement($keys),
                'mood_tags' => fake()->randomElements($moods, rand(2, 4)),
                'danceability' => fake()->randomFloat(2, 0.1, 1.0),
                'energy' => fake()->randomFloat(2, 0.1, 1.0),
                'valence' => fake()->randomFloat(2, 0.1, 1.0),
                'last_analyzed_at' => now()->subDays(rand(0, 30)),
            ]);
        }

        // =============================================
        // 12. PODCASTS — 2 podcasts
        // =============================================
        $podcast1 = Podcast::create([
            'artist_id' => $artists[0]->id,
            'title' => 'Cerita Tulus',
            'description' => 'Podcast yang membahas proses kreatif di balik lagu-lagu Tulus.',
            'cover_image_url' => 'https://picsum.photos/seed/cerita-tulus/300/300',
            'category' => 'Music',
            'is_completed' => false,
        ]);

        $podcast2 = Podcast::create([
            'artist_id' => $artists[7]->id,
            'title' => 'Jejak Perjalanan',
            'description' => 'Fiersa Besari berbagi cerita perjalanan dan inspirasi di balik lagu-lagunya.',
            'cover_image_url' => 'https://picsum.photos/seed/jejak-perjalanan/300/300',
            'category' => 'Travel & Music',
            'is_completed' => false,
        ]);

        // =============================================
        // 13. PODCAST_EPISODES — 5 episodes each
        // =============================================
        $episodesData = [
            ['podcast' => $podcast1, 'episodes' => [
                'Episode 1: Awal Mula',
                'Episode 2: Menulis Hati-Hati di Jalan',
                'Episode 3: Kolaborasi Pertama',
                'Episode 4: Touring Keliling Indonesia',
                'Episode 5: Rencana Masa Depan',
            ]],
            ['podcast' => $podcast2, 'episodes' => [
                'Episode 1: Gunung Pertama',
                'Episode 2: Menulis di Tepi Danau',
                'Episode 3: Dari Bandung ke Flores',
                'Episode 4: Inspirasi dari Alam',
                'Episode 5: Konspirasi dan Alam Semesta',
            ]],
        ];

        foreach ($episodesData as $epGroup) {
            foreach ($epGroup['episodes'] as $idx => $epTitle) {
                PodcastEpisode::create([
                    'podcast_id' => $epGroup['podcast']->id,
                    'title' => $epTitle,
                    'description' => "Deskripsi untuk {$epTitle}. Dengarkan episode menarik ini!",
                    'audio_url' => 'https://res.cloudinary.com/demo/video/upload/sample.mp3',
                    'duration_ms' => fake()->numberBetween(900000, 3600000),
                    'stream_count' => fake()->numberBetween(100, 50000),
                    'release_date' => now()->subWeeks(count($epGroup['episodes']) - $idx)->format('Y-m-d'),
                ]);
            }
        }
    }
}
