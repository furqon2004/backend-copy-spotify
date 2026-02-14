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

        // ──────────────────────────────────────────────
        // 1. Admin
        // ──────────────────────────────────────────────
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

        // ──────────────────────────────────────────────
        // 2. Artists (12 artis)
        // ──────────────────────────────────────────────
        $artistsData = [
            ['name' => 'Tulus', 'slug' => 'tulus', 'bio' => 'Penyanyi dan penulis lagu asal Indonesia, dikenal lewat lagu Monokrom dan Hati-Hati di Jalan.', 'listeners' => 4500000],
            ['name' => 'Pamungkas', 'slug' => 'pamungkas', 'bio' => 'Musisi indie asal Indonesia yang terkenal lewat lagu To The Bone dan I Love You But I\'m Letting Go.', 'listeners' => 3200000],
            ['name' => 'Hindia', 'slug' => 'hindia', 'bio' => 'Baskara Putra atau Hindia, penyanyi dan rapper asal Jakarta.', 'listeners' => 2800000],
            ['name' => 'NIKI', 'slug' => 'niki', 'bio' => 'Nicole Zefanya, penyanyi R&B internasional asal Indonesia di bawah label 88rising.', 'listeners' => 5000000],
            ['name' => 'Rich Brian', 'slug' => 'rich-brian', 'bio' => 'Brian Imanuel, rapper dan produser musik asal Indonesia.', 'listeners' => 4800000],
            ['name' => 'Sal Priadi', 'slug' => 'sal-priadi', 'bio' => 'Musisi kontemporer Indonesia yang dikenal lewat lagu Amin Paling Serius.', 'listeners' => 1200000],
            ['name' => 'Nadin Amizah', 'slug' => 'nadin-amizah', 'bio' => 'Penyanyi folk-pop asal Bandung yang terkenal dengan lagu Bertaut.', 'listeners' => 2500000],
            ['name' => 'Fiersa Besari', 'slug' => 'fiersa-besari', 'bio' => 'Musisi dan penulis asal Bandung, dikenal lewat Waktu Yang Salah dan April.', 'listeners' => 3000000],
            ['name' => 'Raisa', 'slug' => 'raisa', 'bio' => 'Penyanyi pop Indonesia yang terkenal lewat Apalah (Arti Menunggu) dan Could It Be.', 'listeners' => 5500000],
            ['name' => 'Ardhito Pramono', 'slug' => 'ardhito-pramono', 'bio' => 'Musisi jazz-pop asal Indonesia yang dikenal lewat lagu Fine Today.', 'listeners' => 2000000],
            ['name' => 'Danilla', 'slug' => 'danilla', 'bio' => 'Penyanyi dan penulis lagu Indonesia bergenre indie-pop.', 'listeners' => 1800000],
            ['name' => 'Kunto Aji', 'slug' => 'kunto-aji', 'bio' => 'Musisi pop kontemporer Indonesia, dikenal lewat Pilu Membiru dan Rehat.', 'listeners' => 2200000],
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

        // ──────────────────────────────────────────────
        // 3. Regular Users (3 user)
        // ──────────────────────────────────────────────
        $regularUsers = [];
        $usersData = [
            ['email' => 'user@spotify.com', 'username' => 'musiclover', 'full_name' => 'Regular User'],
            ['email' => 'andi@spotify.com', 'username' => 'andisaputra', 'full_name' => 'Andi Saputra'],
            ['email' => 'sari@spotify.com', 'username' => 'sarindah', 'full_name' => 'Sari Indah'],
        ];
        foreach ($usersData as $uData) {
            $regularUsers[] = User::create([
                'id' => Str::uuid(),
                'email' => $uData['email'],
                'username' => $uData['username'],
                'password_hash' => $password,
                'full_name' => $uData['full_name'],
                'is_active' => true,
            ]);
        }

        // ──────────────────────────────────────────────
        // 4. Genres (12 genre)
        // ──────────────────────────────────────────────
        $genresData = ['Pop', 'Rock', 'R&B', 'Hip-Hop', 'Indie', 'Acoustic', 'Jazz', 'Dangdut', 'Electronic', 'Folk', 'Reggae', 'Blues'];
        $genres = [];
        foreach ($genresData as $g) {
            $genres[] = Genre::create([
                'name' => $g,
                'slug' => Str::slug($g),
            ]);
        }

        // ──────────────────────────────────────────────
        // 5. Albums (18 album, ~1-2 per artis)
        // ──────────────────────────────────────────────
        $albumsData = [
            // Tulus
            ['artist_idx' => 0, 'title' => 'Monokrom', 'type' => 'ALBUM', 'tracks' => 10, 'year' => 2016],
            ['artist_idx' => 0, 'title' => 'Manusia', 'type' => 'ALBUM', 'tracks' => 12, 'year' => 2022],
            // Pamungkas
            ['artist_idx' => 1, 'title' => 'Walk The Talk', 'type' => 'ALBUM', 'tracks' => 10, 'year' => 2019],
            ['artist_idx' => 1, 'title' => 'Solipsism', 'type' => 'ALBUM', 'tracks' => 8, 'year' => 2020],
            // Hindia
            ['artist_idx' => 2, 'title' => 'Menari Dengan Bayangan', 'type' => 'ALBUM', 'tracks' => 9, 'year' => 2020],
            // NIKI
            ['artist_idx' => 3, 'title' => 'Moonchild', 'type' => 'ALBUM', 'tracks' => 10, 'year' => 2020],
            ['artist_idx' => 3, 'title' => 'Nicole', 'type' => 'ALBUM', 'tracks' => 14, 'year' => 2022],
            // Rich Brian
            ['artist_idx' => 4, 'title' => 'Amen', 'type' => 'ALBUM', 'tracks' => 13, 'year' => 2018],
            ['artist_idx' => 4, 'title' => '1999', 'type' => 'ALBUM', 'tracks' => 10, 'year' => 2020],
            // Sal Priadi
            ['artist_idx' => 5, 'title' => 'Kulari Dari Senja', 'type' => 'SINGLE', 'tracks' => 3, 'year' => 2021],
            // Nadin Amizah
            ['artist_idx' => 6, 'title' => 'Selamat Ulang Tahun', 'type' => 'ALBUM', 'tracks' => 12, 'year' => 2020],
            // Fiersa Besari
            ['artist_idx' => 7, 'title' => 'Tempat Aku Pulang', 'type' => 'ALBUM', 'tracks' => 10, 'year' => 2018],
            ['artist_idx' => 7, 'title' => 'Konspirasi Alam Semesta', 'type' => 'ALBUM', 'tracks' => 10, 'year' => 2020],
            // Raisa
            ['artist_idx' => 8, 'title' => 'Handmade', 'type' => 'ALBUM', 'tracks' => 12, 'year' => 2016],
            ['artist_idx' => 8, 'title' => 'It\'s Personal', 'type' => 'ALBUM', 'tracks' => 10, 'year' => 2021],
            // Ardhito Pramono
            ['artist_idx' => 9, 'title' => 'A Quiet Afternoon', 'type' => 'ALBUM', 'tracks' => 8, 'year' => 2019],
            // Danilla
            ['artist_idx' => 10, 'title' => 'Lintasan Waktu', 'type' => 'ALBUM', 'tracks' => 10, 'year' => 2017],
            // Kunto Aji
            ['artist_idx' => 11, 'title' => 'Mantra Mantra', 'type' => 'ALBUM', 'tracks' => 11, 'year' => 2018],
        ];

        $albums = [];
        foreach ($albumsData as $idx => $ad) {
            $albums[] = Album::create([
                'artist_id' => $artists[$ad['artist_idx']]->id,
                'title' => $ad['title'],
                'release_date' => now()->subYears(now()->year - $ad['year']),
                'cover_image_url' => 'https://picsum.photos/seed/album' . $idx . '/300/300',
                'type' => $ad['type'],
                'total_tracks' => $ad['tracks'],
            ]);
        }

        // ──────────────────────────────────────────────
        // 6. Songs (60+ lagu realistis)
        // ──────────────────────────────────────────────
        $songsData = [
            // Tulus — Monokrom
            ['album_idx' => 0, 'title' => 'Monokrom', 'dur' => 256],
            ['album_idx' => 0, 'title' => 'Pamit', 'dur' => 284],
            ['album_idx' => 0, 'title' => 'Langit Abu-Abu', 'dur' => 230],
            ['album_idx' => 0, 'title' => 'Ruang Sendiri', 'dur' => 245],
            // Tulus — Manusia
            ['album_idx' => 1, 'title' => 'Hati-Hati di Jalan', 'dur' => 318],
            ['album_idx' => 1, 'title' => 'Interaksi', 'dur' => 225],
            ['album_idx' => 1, 'title' => 'Diri', 'dur' => 236],
            // Pamungkas — Walk The Talk
            ['album_idx' => 2, 'title' => 'To The Bone', 'dur' => 203],
            ['album_idx' => 2, 'title' => 'I Love You But I\'m Letting Go', 'dur' => 275],
            ['album_idx' => 2, 'title' => 'One Only', 'dur' => 192],
            ['album_idx' => 2, 'title' => 'Sorry', 'dur' => 214],
            // Pamungkas — Solipsism
            ['album_idx' => 3, 'title' => 'Closure', 'dur' => 224],
            ['album_idx' => 3, 'title' => 'Be Alright', 'dur' => 210],
            ['album_idx' => 3, 'title' => 'Rider', 'dur' => 198],
            // Hindia — Menari Dengan Bayangan
            ['album_idx' => 4, 'title' => 'Secukupnya', 'dur' => 260],
            ['album_idx' => 4, 'title' => 'Evaluasi', 'dur' => 232],
            ['album_idx' => 4, 'title' => 'Membasuh', 'dur' => 215],
            ['album_idx' => 4, 'title' => 'Rumah Ke Rumah', 'dur' => 248],
            // NIKI — Moonchild
            ['album_idx' => 5, 'title' => 'Switchblade', 'dur' => 192],
            ['album_idx' => 5, 'title' => 'Nightcrawlers', 'dur' => 210],
            ['album_idx' => 5, 'title' => 'Selene', 'dur' => 264],
            ['album_idx' => 5, 'title' => 'Lose', 'dur' => 198],
            // NIKI — Nicole
            ['album_idx' => 6, 'title' => 'Before', 'dur' => 215],
            ['album_idx' => 6, 'title' => 'High School in Jakarta', 'dur' => 240],
            ['album_idx' => 6, 'title' => 'Backburner', 'dur' => 200],
            ['album_idx' => 6, 'title' => 'Ocean Eyes', 'dur' => 230],
            // Rich Brian — Amen
            ['album_idx' => 7, 'title' => 'Amen', 'dur' => 188],
            ['album_idx' => 7, 'title' => 'See Me', 'dur' => 176],
            ['album_idx' => 7, 'title' => 'Glow Like Dat', 'dur' => 196],
            ['album_idx' => 7, 'title' => 'Cold', 'dur' => 204],
            // Rich Brian — 1999
            ['album_idx' => 8, 'title' => 'Love In My Pocket', 'dur' => 195],
            ['album_idx' => 8, 'title' => '100 Degrees', 'dur' => 187],
            ['album_idx' => 8, 'title' => 'DOA', 'dur' => 210],
            // Sal Priadi — Kulari Dari Senja
            ['album_idx' => 9, 'title' => 'Amin Paling Serius', 'dur' => 285],
            ['album_idx' => 9, 'title' => 'Ipar Adalah Maut', 'dur' => 264],
            // Nadin Amizah — Selamat Ulang Tahun
            ['album_idx' => 10, 'title' => 'Bertaut', 'dur' => 298],
            ['album_idx' => 10, 'title' => 'Sorai', 'dur' => 262],
            ['album_idx' => 10, 'title' => 'Rumpang', 'dur' => 240],
            ['album_idx' => 10, 'title' => 'Seperti Tulang', 'dur' => 215],
            // Fiersa Besari — Tempat Aku Pulang
            ['album_idx' => 11, 'title' => 'Waktu Yang Salah', 'dur' => 310],
            ['album_idx' => 11, 'title' => 'Celengan Rindu', 'dur' => 268],
            ['album_idx' => 11, 'title' => 'April', 'dur' => 290],
            ['album_idx' => 11, 'title' => 'Nadir', 'dur' => 245],
            // Fiersa Besari — Konspirasi Alam Semesta
            ['album_idx' => 12, 'title' => 'Garis Terdepan', 'dur' => 272],
            ['album_idx' => 12, 'title' => 'Bel Sekolah', 'dur' => 256],
            // Raisa — Handmade
            ['album_idx' => 13, 'title' => 'Apalah (Arti Menunggu)', 'dur' => 245],
            ['album_idx' => 13, 'title' => 'Kali Kedua', 'dur' => 268],
            ['album_idx' => 13, 'title' => 'Could It Be', 'dur' => 234],
            ['album_idx' => 13, 'title' => 'Letting You Go', 'dur' => 252],
            // Raisa — It's Personal
            ['album_idx' => 14, 'title' => 'Cinta Sederhana', 'dur' => 229],
            ['album_idx' => 14, 'title' => 'Bahasa Kalbu', 'dur' => 242],
            ['album_idx' => 14, 'title' => 'Someday', 'dur' => 218],
            // Ardhito Pramono — A Quiet Afternoon
            ['album_idx' => 15, 'title' => 'Fine Today', 'dur' => 212],
            ['album_idx' => 15, 'title' => 'Bitterlove', 'dur' => 228],
            ['album_idx' => 15, 'title' => 'I Just Couldn\'t Save You Tonight', 'dur' => 260],
            // Danilla — Lintasan Waktu
            ['album_idx' => 16, 'title' => 'Lintasan Waktu', 'dur' => 234],
            ['album_idx' => 16, 'title' => 'Ingin Kumiliki', 'dur' => 216],
            ['album_idx' => 16, 'title' => 'Senja di Ambang Pilu', 'dur' => 248],
            // Kunto Aji — Mantra Mantra
            ['album_idx' => 17, 'title' => 'Pilu Membiru', 'dur' => 254],
            ['album_idx' => 17, 'title' => 'Rehat', 'dur' => 236],
            ['album_idx' => 17, 'title' => 'Topik Semalam', 'dur' => 220],
            ['album_idx' => 17, 'title' => 'Akhir Bulan', 'dur' => 208],
        ];

        $moods = ['happy', 'sad', 'energetic', 'calm', 'romantic', 'melancholic', 'chill', 'upbeat', 'dark', 'nostalgic'];
        $keys = ['C', 'C#', 'D', 'D#', 'E', 'F', 'F#', 'G', 'G#', 'A', 'A#', 'B'];

        $songs = [];
        foreach ($songsData as $sd) {
            $album = $albums[$sd['album_idx']];
            $title = $sd['title'];

            $songs[] = Song::create([
                'id' => Str::uuid(),
                'album_id' => $album->id,
                'artist_id' => $album->artist_id,
                'title' => $title,
                'slug' => Str::slug($title) . '-' . Str::random(5),
                'duration_seconds' => $sd['dur'],
                'file_path' => 'https://res.cloudinary.com/dkqwi4lk9/video/upload/v1771061268/DJ_VOICE_IN_MY_HEAD_BREAKBEAT_Slowed_Reverb_btek2r.mp3',
                'file_size' => fake()->numberBetween(3000000, 9000000),
                'cover_url' => $album->cover_image_url,
                'stream_count' => fake()->numberBetween(10000, 5000000),
            ]);
        }

        // ──────────────────────────────────────────────
        // 7. Song-Genre pivot + AI Metadata
        // ──────────────────────────────────────────────
        foreach ($songs as $song) {
            // Assign 1-3 genres
            $assignedGenres = fake()->randomElements($genres, fake()->numberBetween(1, 3));
            foreach ($assignedGenres as $genre) {
                DB::table('song_genres')->insert([
                    'song_id' => $song->id,
                    'genre_id' => $genre->id,
                ]);
            }

            // AI Metadata
            SongAiMetadata::create([
                'song_id' => $song->id,
                'bpm' => fake()->numberBetween(70, 180),
                'key_signature' => $keys[array_rand($keys)],
                'mood_tags' => fake()->randomElements($moods, fake()->numberBetween(2, 4)),
                'energy_score' => fake()->randomFloat(2, 0, 1),
            ]);
        }

        // ──────────────────────────────────────────────
        // 8. Playlists (3 playlist)
        // ──────────────────────────────────────────────
        $playlistsData = [
            ['user_idx' => 0, 'name' => 'Indonesian Hits 2024', 'desc' => 'Kumpulan lagu Indonesia terpopuler'],
            ['user_idx' => 0, 'name' => 'Late Night Vibes', 'desc' => 'Lagu-lagu untuk menemani malam'],
            ['user_idx' => 1, 'name' => 'Road Trip Indonesia', 'desc' => 'Playlist untuk perjalanan jauh'],
        ];

        foreach ($playlistsData as $pd) {
            $playlist = Playlist::create([
                'user_id' => $regularUsers[$pd['user_idx']]->id,
                'name' => $pd['name'],
                'description' => $pd['desc'],
                'is_public' => true,
            ]);

            // Add 8-12 random songs
            $playlistSongs = collect($songs)->shuffle()->take(fake()->numberBetween(8, 12));
            foreach ($playlistSongs as $pos => $song) {
                PlaylistItem::create([
                    'playlist_id' => $playlist->id,
                    'song_id' => $song->id,
                    'position' => $pos + 1,
                ]);
            }
        }

        // ──────────────────────────────────────────────
        // 9. Liked Songs
        // ──────────────────────────────────────────────
        foreach ($regularUsers as $user) {
            $likedSongs = collect($songs)->shuffle()->take(fake()->numberBetween(10, 20));
            foreach ($likedSongs as $song) {
                DB::table('liked_songs')->insert([
                    'user_id' => $user->id,
                    'song_id' => $song->id,
                    'liked_at' => fake()->dateTimeBetween('-6 months'),
                ]);
            }
        }

        // ──────────────────────────────────────────────
        // 10. Podcasts + Episodes
        // ──────────────────────────────────────────────
        $podcastsData = [
            ['artist_idx' => 2, 'title' => 'Hindia Talks', 'desc' => 'Obrolan santai soal musik dan kehidupan', 'cat' => 'Music'],
            ['artist_idx' => 6, 'title' => 'Nadin di Sore Hari', 'desc' => 'Cerita dan refleksi dari Nadin Amizah', 'cat' => 'Arts'],
            ['artist_idx' => 9, 'title' => 'Jazz Corner', 'desc' => 'Diskusi tentang jazz dan musik kontemporer', 'cat' => 'Music'],
        ];

        foreach ($podcastsData as $pidx => $pd) {
            $podcast = Podcast::create([
                'artist_id' => $artists[$pd['artist_idx']]->id,
                'title' => $pd['title'],
                'description' => $pd['desc'],
                'category' => $pd['cat'],
                'cover_image_url' => 'https://picsum.photos/seed/podcast' . $pidx . '/300/300',
            ]);

            // 3–5 episodes per podcast
            $epCount = fake()->numberBetween(3, 5);
            for ($i = 1; $i <= $epCount; $i++) {
                PodcastEpisode::create([
                    'podcast_id' => $podcast->id,
                    'title' => 'Episode ' . $i . ': ' . fake()->sentence(4),
                    'description' => fake()->paragraph(),
                    'audio_url' => 'https://res.cloudinary.com/dkqwi4lk9/video/upload/v1771061268/DJ_VOICE_IN_MY_HEAD_BREAKBEAT_Slowed_Reverb_btek2r.mp3',
                    'duration_ms' => fake()->numberBetween(600000, 3600000),
                    'release_date' => fake()->dateTimeBetween('-1 year'),
                ]);
            }
        }

        // ──────────────────────────────────────────────
        // 11. Stream History
        // ──────────────────────────────────────────────
        foreach ($regularUsers as $user) {
            $streamedSongs = collect($songs)->shuffle()->take(15);
            foreach ($streamedSongs as $song) {
                StreamHistory::create([
                    'user_id' => $user->id,
                    'song_id' => $song->id,
                    'played_at' => fake()->dateTimeBetween('-3 months'),
                    'duration_played_ms' => fake()->numberBetween(30000, $song->duration_seconds * 1000),
                    'source' => fake()->randomElement(['SEARCH', 'PLAYLIST', 'AI_RECOMMENDATION']),
                ]);
            }
        }

        // ──────────────────────────────────────────────
        // 12. Content Reports
        // ──────────────────────────────────────────────
        $reportReasons = [
            'Konten mengandung kata-kata kasar',
            'Hak cipta dilanggar',
            'Konten tidak pantas untuk semua usia',
            'Audio berkualitas rendah / rusak',
        ];

        for ($i = 0; $i < 5; $i++) {
            ContentReport::create([
                'reporter_id' => $regularUsers[array_rand($regularUsers)]->id,
                'target_type' => fake()->randomElement(['SONG', 'PLAYLIST']),
                'target_id' => $songs[array_rand($songs)]->id,
                'reason' => $reportReasons[array_rand($reportReasons)],
                'status' => fake()->randomElement(['PENDING', 'RESOLVED', 'REJECTED']),
            ]);
        }
    }
}