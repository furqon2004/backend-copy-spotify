<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Album;
use App\Models\Artist;
use App\Models\ContentReport;
use App\Models\Genre;
use App\Models\Lyric;
use App\Models\Playlist;
use App\Models\PlaylistItem;
use App\Models\Podcast;
use App\Models\PodcastEpisode;
use App\Models\Song;
use App\Models\SongAiMetadata;
use App\Models\StreamHistory;
use App\Models\User;
use Carbon\Carbon;
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
        $now = Carbon::now();
        $password = Hash::make('password');

        $adminId = Str::uuid()->toString();
        User::insert([[
            'id' => $adminId,
            'email' => 'admin@spotify.com',
            'username' => 'admin',
            'password_hash' => $password,
            'full_name' => 'Super Admin',
            'profile_image_url' => 'https://picsum.photos/seed/admin/300/300',
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]]);
        Admin::insert([['id' => Str::uuid()->toString(), 'user_id' => $adminId, 'created_at' => $now, 'updated_at' => $now]]);

        $usersData = [
            ['id' => Str::uuid()->toString(), 'email' => 'user@spotify.com', 'username' => 'musiclover', 'full_name' => 'Regular User'],
            ['id' => Str::uuid()->toString(), 'email' => 'andi@spotify.com', 'username' => 'andisaputra', 'full_name' => 'Andi Saputra'],
            ['id' => Str::uuid()->toString(), 'email' => 'sari@spotify.com', 'username' => 'sarindah', 'full_name' => 'Sari Indah'],
        ];

        $usersInsert = [];
        foreach ($usersData as $u) {
            $usersInsert[] = [
                'id' => $u['id'],
                'email' => $u['email'],
                'username' => $u['username'],
                'password_hash' => $password,
                'full_name' => $u['full_name'],
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        User::insert($usersInsert);

        $artistsRaw = [
            'Hindia', 'Kunto Aji', 'for Revenge', 'Kaleb J', 'Kahitna ft Monita', 
            'Glenn Fredly', 'Chrisye', 'Dewa', 'Bring Me The Horizon', 'Billie Eilish', 
            'Elvis Presley', 'Swedish House Mafia ft The Weeknd', 'PMVATT', 'Oasis', 
            'Bernadya', 'Sal Priadi', 'Nadin Amizah', 'Barasuara', 'Mahalini', 'Linkin Park',
            'Slipknot', 'Noel Gallagher\'s High Flying Birds', 'LANY', 'Paramore',
            'Sydney Rose', 'Mariah Carey & Boyz II Men', 'My Chemical Romance',
            'Rico Blanco', 'Evanescence', 'Radiohead', 'Bruno Mars', 'Sienna Spiro', 'Taylor Swift'
        ];

        $usersArtistInsert = [];
        $artistsInsert = [];
        $artistMap = [];

        foreach ($artistsRaw as $artistName) {
            $userId = Str::uuid()->toString();
            $artistId = Str::uuid()->toString();
            $slug = Str::slug($artistName);

            $usersArtistInsert[] = [
                'id' => $userId,
                'email' => $slug . '@spotify.com',
                'username' => $slug,
                'password_hash' => $password,
                'full_name' => $artistName,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $artistsInsert[] = [
                'id' => $artistId,
                'user_id' => $userId,
                'name' => $artistName,
                'slug' => $slug,
                'bio' => 'Official artist profile for ' . $artistName,
                'avatar_url' => 'https://picsum.photos/seed/' . $slug . '/300/300',
                'monthly_listeners' => rand(1000000, 5000000),
                'is_verified' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $artistMap[$artistName] = $artistId;
        }

        User::insert($usersArtistInsert);
        Artist::insert($artistsInsert);

        $albumsInsert = [];
        $albumMap = [];

        foreach ($artistsRaw as $artistName) {
            $albumId = Str::uuid()->toString();
            $albumsInsert[] = [
                'id' => $albumId,
                'artist_id' => $artistMap[$artistName],
                'title' => $artistName . ' Essentials',
                'release_date' => $now->copy()->subYears(rand(1, 10)),
                'cover_image_url' => 'https://picsum.photos/seed/album_' . Str::slug($artistName) . '/300/300',
                'type' => 'ALBUM',
                'total_tracks' => 10,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $albumMap[$artistName] = $albumId;
        }

        Album::insert($albumsInsert);

        $songsRaw = [
            // ─── Batch 1 (original) ───────────────────────────────────────
            ['Hindia', 'Everything U Are', 'https://res.cloudinary.com/dkqwi4lk9/video/upload/v1772181912/Hindia_-_everything_u_are_l4dnni.mp3'],
            ['Kunto Aji', 'Pilu Membiru', 'https://res.cloudinary.com/dkqwi4lk9/video/upload/v1772181911/Kunto_Aji_-_Pilu_Membiru_Official_Audio_z9j95z.mp3'],
            ['for Revenge', 'Penyangkalan', 'https://res.cloudinary.com/dkqwi4lk9/video/upload/v1772181896/for_Revenge_-_Penyangkalan_Official_Music_Video_nmlfrq.mp3'],
            ['for Revenge', 'Pulang', 'https://res.cloudinary.com/dkqwi4lk9/video/upload/v1772181892/For_Revenge_-_Pulang_Official_Lyric_Video_pdoia6.mp3'],
            ['Kaleb J', 'Di Balik Pertanda', 'https://res.cloudinary.com/dkqwi4lk9/video/upload/v1772181914/Kaleb_J_-_Di_Balik_Pertanda_Official_Lyric_Video_Visualizer_jbxqjn.mp3'],
            ['Kahitna ft Monita', 'Titik Nadir', 'https://res.cloudinary.com/dkqwi4lk9/video/upload/v1772181913/Kahitna_Feat._Monita_Tahalea_-_Titik_Nadir_Official_Music_Video_lm4yo3.mp3'],
            ['for Revenge', 'Ada Selamanya', 'https://res.cloudinary.com/dkqwi4lk9/video/upload/v1772181886/For_Revenge_Fiersa_Besari_-_Ada_Selamanya_Official_Music_Video_pda2ce.mp3'],
            ['Glenn Fredly', 'Kasih Putih', 'https://res.cloudinary.com/dkqwi4lk9/video/upload/v1772181896/Glenn_Fredly_-_Kasih_Putih_qsuhvk.mp3'],
            ['Chrisye', 'Untukku', 'https://res.cloudinary.com/dkqwi4lk9/video/upload/v1772181884/Chrisye_-_Untukku_Official_Music_Video_vsdkpj.mp3'],
            ['Dewa', 'Risalah Hati', 'https://res.cloudinary.com/dkqwi4lk9/video/upload/v1772181880/Dewa_-_Risalah_Hati_Official_Video_zdv8bx.mp3'],
            ['Bring Me The Horizon', 'Drown', 'https://res.cloudinary.com/dkqwi4lk9/video/upload/v1772181864/Bring_Me_The_Horizon_-_Drown_hjubhw.mp3'],
            ['Billie Eilish', 'i dont wanna be you anymore', 'https://res.cloudinary.com/dkqwi4lk9/video/upload/v1772181862/Billie_Eilish_-_idontwannabeyouanymore_Official_Vertical_Video_wnp3bm.mp3'],
            ['Elvis Presley', 'Cant Help Falling in Love', 'https://res.cloudinary.com/dkqwi4lk9/video/upload/v1772181861/Elvis_Presley_-_Can_t_Help_Falling_in_Love_Lyrics_wp5rvt.mp3'],
            ['Swedish House Mafia ft The Weeknd', 'Moth To A Flame', 'https://res.cloudinary.com/dkqwi4lk9/video/upload/v1772181861/Swedish_House_Mafia_ft._The_Weeknd_-_Moth_To_A_Flame_Lyrics_bnuzmd.mp3'],
            ['PMVATT', 'Satu Tujuan', 'https://res.cloudinary.com/dkqwi4lk9/video/upload/v1772181846/Satu_Tujuan_-_PMVATT_Official_Music_Video_bjuc4t.mp3'],
            ['Oasis', 'Slide Away', 'https://res.cloudinary.com/dkqwi4lk9/video/upload/v1772181845/Oasis_-_Slide_Away_Official_Lyric_Video_gd4wtx.mp3'],
            ['Bernadya', 'Lama-Lama', 'https://res.cloudinary.com/dkqwi4lk9/video/upload/v1772181844/Bernadya_-_Lama-Lama_Official_Video_g2vzeu.mp3'],
            ['Sal Priadi', 'Kita usahakan rumah itu', 'https://res.cloudinary.com/dkqwi4lk9/video/upload/v1772181832/Sal_Priadi_-_Kita_usahakan_rumah_itu_Official_Lyric_Video_l6l8qw.mp3'],
            ['Nadin Amizah', 'Di Akhir Perang', 'https://res.cloudinary.com/dkqwi4lk9/video/upload/v1772181829/Nadin_Amizah_-_Di_Akhir_Perang_Official_Lyric_Video_phnlot.mp3'],
            ['Barasuara', 'Terbuang Dalam Waktu', 'https://res.cloudinary.com/dkqwi4lk9/video/upload/v1772181827/Barasuara_-_Terbuang_Dalam_Waktu_Official_Video_ohphjt.mp3'],
            ['Bernadya', 'Kini Mereka Tahu', 'https://res.cloudinary.com/dkqwi4lk9/video/upload/v1772181823/Bernadya_-_Kini_Mereka_Tahu_Official_Video_pbjdqv.mp3'],
            ['Kunto Aji', 'Rehat', 'https://res.cloudinary.com/dkqwi4lk9/video/upload/v1772181815/Kunto_Aji_-_Rehat_Official_Music_Video_v2w4jb.mp3'],
            ['Bernadya', 'Berlari', 'https://res.cloudinary.com/dkqwi4lk9/video/upload/v1772181806/Bernadya_-_Berlari_Official_Video_k0qtgi.mp3'],
            ['Mahalini', 'Bawa Dia Kembali', 'https://res.cloudinary.com/dkqwi4lk9/video/upload/v1772181802/MAHALINI_-_BAWA_DIA_KEMBALI_FABULA_VIDEO_LIRIK_tyesb4.mp3'],
            ['Linkin Park', 'Lost', 'https://res.cloudinary.com/dkqwi4lk9/video/upload/v1772181794/Lost_Official_Music_Video_-_Linkin_Park_hlc84e.mp3'],

            // ─── Batch 2 (new songs) ─────────────────────────────────────
            ['Slipknot', 'Snuff', 'https://res.cloudinary.com/dkqwi4lk9/video/upload/v1772267627/Snuff_hxeo9r.mp3'],
            ['Nadin Amizah', 'Taruh', 'https://res.cloudinary.com/dkqwi4lk9/video/upload/v1772267625/Taruh_gw5olo.mp3'],
            ['Noel Gallagher\'s High Flying Birds', 'If I Had a Gun', 'https://res.cloudinary.com/dkqwi4lk9/video/upload/v1772269680/If_I_Had_A_Gun_lgu2z0.mp3'],
            ['LANY', 'Alonica', 'https://res.cloudinary.com/dkqwi4lk9/video/upload/v1772269666/Alonica_yiac5x.mp3'],
            ['Hindia', 'Cincin', 'https://res.cloudinary.com/dkqwi4lk9/video/upload/v1772269669/Cincin_j32hww.mp3'],
            ['Paramore', 'The Only Exception', 'https://res.cloudinary.com/dkqwi4lk9/video/upload/v1772267626/The_Only_Exception_akyxsa.mp3'],
            ['Nadin Amizah', 'Cermin', 'https://res.cloudinary.com/dkqwi4lk9/video/upload/v1772269681/Cermin_kqrtgr.mp3'],
            ['Sydney Rose', 'We Hug Now', 'https://res.cloudinary.com/dkqwi4lk9/video/upload/v1772267630/We_Hug_Now_f7dpkb.mp3'],
            ['Mariah Carey & Boyz II Men', 'One Sweet Day', 'https://res.cloudinary.com/dkqwi4lk9/video/upload/v1772267622/One_Sweet_Day_hftmkb.mp3'],
            ['Sal Priadi', 'Ada Titik-Titik di Ujung Doa', 'https://res.cloudinary.com/dkqwi4lk9/video/upload/v1772267634/Ada_titik-titik_di_ujung_doa_l1p3zs.mp3'],
            ['My Chemical Romance', 'I Don\'t Love You', 'https://res.cloudinary.com/dkqwi4lk9/video/upload/v1772269682/I_Don_t_Love_You_llkctw.mp3'],
            ['Rico Blanco', 'Your Universe', 'https://res.cloudinary.com/dkqwi4lk9/video/upload/v1772267633/Your_Universe_q08qsj.mp3'],
            ['for Revenge', 'Serana', 'https://res.cloudinary.com/dkqwi4lk9/video/upload/v1772267624/Serana_czdx94.mp3'],
            ['Oasis', 'Don\'t Go Away', 'https://res.cloudinary.com/dkqwi4lk9/video/upload/v1772269673/Don_t_Go_Away_wme6mi.mp3'],
            ['Evanescence', 'Bring Me to Life', 'https://res.cloudinary.com/dkqwi4lk9/video/upload/v1772269670/Bring_Me_To_Life_cczxhz.mp3'],
            ['Radiohead', 'Creep', 'https://res.cloudinary.com/dkqwi4lk9/video/upload/v1772269673/Creep_bmbizj.mp3'],
            ['Oasis', 'Wonderwall', 'https://res.cloudinary.com/dkqwi4lk9/video/upload/v1772267634/Wonderwall_zltpio.mp3'],
            ['Bruno Mars', 'Talking to the Moon', 'https://res.cloudinary.com/dkqwi4lk9/video/upload/v1772267626/Talking_to_the_Moon_vkmkl7.mp3'],
            ['Sienna Spiro', 'Die On This Hill', 'https://res.cloudinary.com/dkqwi4lk9/video/upload/v1772269685/Die_On_This_Hill_fzyjhm.mp3'],
            ['Taylor Swift', 'All Too Well', 'https://res.cloudinary.com/dkqwi4lk9/video/upload/v1772267634/All_Too_Well_Taylor_s_Version_uqvg8e.mp3'],
            ['Oasis', 'Stand by Me', 'https://res.cloudinary.com/dkqwi4lk9/video/upload/v1772267631/Stand_by_Me_sbxp2h.mp3'],
            ['Hindia', 'Nina', 'https://res.cloudinary.com/dkqwi4lk9/video/upload/v1772269682/Nina_mlk936.mp3'],
            ['Billie Eilish', 'Watch', 'https://res.cloudinary.com/dkqwi4lk9/video/upload/v1772267628/watch_zj8prx.mp3'],
            ['Sal Priadi', 'Gala Bunga Matahari', 'https://res.cloudinary.com/dkqwi4lk9/video/upload/v1772269675/Gala_bunga_matahari_yzqozz.mp3'],
            ['Hindia', 'Membasuh', 'https://res.cloudinary.com/dkqwi4lk9/video/upload/v1772269684/Membasuh_awpcyx.mp3'],
        ];

        $songsInsert = [];
        $lyricsInsert = [];
        $songAiInsert = [];
        $songIds = [];
        $keys = ['C', 'C#', 'D', 'D#', 'E', 'F', 'F#', 'G', 'G#', 'A', 'A#', 'B'];
        $moods = ['happy', 'sad', 'energetic', 'calm', 'romantic'];

        foreach ($songsRaw as $s) {
            $songId = Str::uuid()->toString();
            $songIds[] = $songId;
            $duration = rand(180, 300);

            $songsInsert[] = [
                'id' => $songId,
                'album_id' => $albumMap[$s[0]],
                'artist_id' => $artistMap[$s[0]],
                'title' => $s[1],
                'slug' => Str::slug($s[1]) . '-' . Str::random(5),
                'duration_seconds' => $duration,
                'file_path' => $s[2],
                'file_size' => rand(3000000, 9000000),
                'cover_url' => 'https://picsum.photos/seed/' . Str::slug($s[1]) . '/300/300',
                'stream_count' => rand(500000, 10000000),
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $content = "Lirik untuk lagu {$s[1]}\nBelum tersedia saat ini";
            $lyricsInsert[] = [
                'id' => Str::uuid()->toString(),
                'song_id' => $songId,
                'content' => $content,
                'synced_lyrics' => json_encode($this->generateSyncedLyrics($content, $duration)),
                'language' => 'id',
                'source' => 'manual',
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $songAiInsert[] = [
                'id' => Str::uuid()->toString(),
                'song_id' => $songId,
                'bpm' => rand(70, 180),
                'key_signature' => $keys[array_rand($keys)],
                'mood_tags' => json_encode([$moods[array_rand($moods)]]),
                'energy_score' => rand(0, 100) / 100,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        $chunkSize = 500;
        foreach (array_chunk($songsInsert, $chunkSize) as $chunk) {
            Song::insert($chunk);
        }
        foreach (array_chunk($lyricsInsert, $chunkSize) as $chunk) {
            Lyric::insert($chunk);
        }
        foreach (array_chunk($songAiInsert, $chunkSize) as $chunk) {
            SongAiMetadata::insert($chunk);
        }

        $genresData = ['Pop', 'Rock', 'R&B', 'Hip-Hop', 'Indie', 'Acoustic', 'Jazz', 'Electronic'];
        $genresInsert = [];
        $genreIds = [];
        
        foreach ($genresData as $g) {
            $genreId = Str::uuid()->toString();
            $genreIds[] = $genreId;
            $genresInsert[] = [
                'id' => $genreId,
                'name' => $g,
                'slug' => Str::slug($g),
                'color' => sprintf('#%06X', mt_rand(0, 0xFFFFFF)),
                'image_url' => 'https://picsum.photos/seed/genre_' . Str::slug($g) . '/300/300',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        Genre::insert($genresInsert);

        $songGenreInsert = [];
        foreach ($songIds as $sId) {
            $songGenreInsert[] = [
                'song_id' => $sId,
                'genre_id' => $genreIds[array_rand($genreIds)],
            ];
        }
        DB::table('song_genres')->insert($songGenreInsert);

        $playlistsInsert = [];
        $playlistItemsInsert = [];
        
        $playlistId = Str::uuid()->toString();
        $playlistsInsert[] = [
            'id' => $playlistId,
            'user_id' => $usersData[0]['id'],
            'name' => 'My Favorite Tracks',
            'description' => 'Kumpulan lagu terbaru dari Cloudinary',
            'is_public' => true,
            'cover_url' => 'https://picsum.photos/seed/my_fav_tracks/300/300',
            'created_at' => $now,
            'updated_at' => $now,
        ];

        foreach (array_slice($songIds, 0, 10) as $idx => $sId) {
            $playlistItemsInsert[] = [
                'id' => Str::uuid()->toString(),
                'playlist_id' => $playlistId,
                'song_id' => $sId,
                'position' => $idx + 1,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // Playlist 2 - Top Hits Indo
        $playlistId2 = Str::uuid()->toString();
        $playlistsInsert[] = [
            'id' => $playlistId2,
            'user_id' => $usersData[1]['id'],
            'name' => 'Top Hits Indonesia',
            'description' => 'Lagu-lagu hits Indonesia terpopuler saat ini',
            'is_public' => true,
            'cover_url' => 'https://picsum.photos/seed/top_hits_indo/300/300',
            'created_at' => $now,
            'updated_at' => $now,
        ];

        foreach (array_slice($songIds, 10, 10) as $idx => $sId) {
            $playlistItemsInsert[] = [
                'id' => Str::uuid()->toString(),
                'playlist_id' => $playlistId2,
                'song_id' => $sId,
                'position' => $idx + 1,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // Playlist 3 - Chill Vibes
        $playlistId3 = Str::uuid()->toString();
        $playlistsInsert[] = [
            'id' => $playlistId3,
            'user_id' => $usersData[2]['id'],
            'name' => 'Chill Vibes',
            'description' => 'Lagu santai untuk menemani harimu',
            'is_public' => true,
            'cover_url' => 'https://picsum.photos/seed/chill_vibes/300/300',
            'created_at' => $now,
            'updated_at' => $now,
        ];

        foreach (array_slice($songIds, 20, 10) as $idx => $sId) {
            $playlistItemsInsert[] = [
                'id' => Str::uuid()->toString(),
                'playlist_id' => $playlistId3,
                'song_id' => $sId,
                'position' => $idx + 1,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        Playlist::insert($playlistsInsert);
        PlaylistItem::insert($playlistItemsInsert);

        // ─── Stream History (Recently Played) ─────────────────────────────
        $streamInsert = [];
        $sources = ['PLAYLIST', 'SEARCH', 'AI_RECOMMENDATION'];

        foreach ($usersData as $userData) {
            // Each user has listened to 15-25 random songs
            $listenedSongs = array_rand(array_flip($songIds), min(20, count($songIds)));
            foreach ($listenedSongs as $idx => $sId) {
                $streamInsert[] = [
                    'id' => Str::uuid()->toString(),
                    'user_id' => $userData['id'],
                    'song_id' => $sId,
                    'played_at' => $now->copy()->subMinutes(rand(1, 1440 * 7)), // Within last 7 days
                    'duration_played_ms' => rand(60000, 300000),
                    'source' => $sources[array_rand($sources)],
                    'device' => 'web',
                ];
            }
        }

        foreach (array_chunk($streamInsert, 500) as $chunk) {
            StreamHistory::insert($chunk);
        }

        // ─── Podcast Artists ──────────────────────────────────────────────
        $podcastArtistsRaw = [
            'Sule', 'Pandji Pragiwaksono', 'Felix Siauw', 'Gofar Hilman',
            'Ustadz Jojo', 'Praz Teguh', 'Abdur Arsyad', 'Coki Pardede', 'Rizky Febian',
        ];

        $podcastArtistUsersInsert = [];
        $podcastArtistsInsert = [];
        $podcastArtistMap = [];

        foreach ($podcastArtistsRaw as $name) {
            $userId = Str::uuid()->toString();
            $artistId = Str::uuid()->toString();
            $slug = Str::slug($name);

            $podcastArtistUsersInsert[] = [
                'id' => $userId,
                'email' => $slug . '@spotify.com',
                'username' => $slug,
                'password_hash' => $password,
                'full_name' => $name,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $podcastArtistsInsert[] = [
                'id' => $artistId,
                'user_id' => $userId,
                'name' => $name,
                'slug' => $slug,
                'bio' => 'Podcaster & public figure — ' . $name,
                'avatar_url' => 'https://picsum.photos/seed/' . $slug . '/300/300',
                'monthly_listeners' => rand(500000, 3000000),
                'is_verified' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $podcastArtistMap[$name] = $artistId;
        }

        User::insert($podcastArtistUsersInsert);
        Artist::insert($podcastArtistsInsert);

        // ─── Podcasts ─────────────────────────────────────────────────────
        $pwkPodcastId = Str::uuid()->toString();
        $kejarSetoranId = Str::uuid()->toString();

        Podcast::insert([
            [
                'id' => $pwkPodcastId,
                'artist_id' => $podcastArtistMap['Pandji Pragiwaksono'],
                'title' => 'PWK (Pernah Waktu Kecil)',
                'description' => 'Podcast PWK menghadirkan bintang tamu selebriti dan public figure Indonesia dalam obrolan seru, lucu, dan penuh cerita hidup.',
                'cover_image_url' => 'https://picsum.photos/seed/pwk-podcast/300/300',
                'category' => 'Comedy',
                'is_completed' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => $kejarSetoranId,
                'artist_id' => $podcastArtistMap['Praz Teguh'],
                'title' => 'Kejar Setoran',
                'description' => 'Podcast komedi stand-up dengan para komika Indonesia membahas pengalaman hidup yang kocak dan relatable.',
                'cover_image_url' => 'https://picsum.photos/seed/kejar-setoran/300/300',
                'category' => 'Comedy',
                'is_completed' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        // ─── Podcast Episodes ─────────────────────────────────────────────
        PodcastEpisode::insert([
            // PWK Episodes
            [
                'id' => Str::uuid()->toString(),
                'podcast_id' => $pwkPodcastId,
                'title' => 'DIKASIH MOBIL SAMA RIZKY FEBIAN SULE MALAH HARUS NYICIL',
                'description' => 'Sule curhat soal hadiah mobil dari Rizky Febian yang ternyata harus dicicil. Obrolan seru bareng Sule & Rizky Febian di PWK!',
                'audio_url' => 'https://res.cloudinary.com/dkqwi4lk9/video/upload/v1772700410/PWK_DIKASIH_MOBIL_SAMA_RIZKY_FEBIAN_SULE_MALAH_HARUS_NYICIL___LQgIdvOy38E_qpkajk.mp3',
                'duration_ms' => 3720000,
                'stream_count' => rand(800000, 2500000),
                'release_date' => $now->copy()->subDays(14),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => Str::uuid()->toString(),
                'podcast_id' => $pwkPodcastId,
                'title' => 'PERNAH DI ROASTING PANDJI USTAD FELIX SIAUW SEKARANG MALAH BIKIN KONTEN BARENG',
                'description' => 'Ustadz Felix Siauw cerita soal pernah di-roasting Pandji dan sekarang justru berkolaborasi bikin konten bareng.',
                'audio_url' => 'https://res.cloudinary.com/dkqwi4lk9/video/upload/v1772700409/PWK_PERNAH_DI_ROASTING_PANDJI_USTAD_FELIX_SIAUW_SEKARANG_MALAH_BIKIN_KONTEN_BARENG___n3vLnBLrsXs_ctqjqq.mp3',
                'duration_ms' => 4200000,
                'stream_count' => rand(600000, 2000000),
                'release_date' => $now->copy()->subDays(21),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => Str::uuid()->toString(),
                'podcast_id' => $pwkPodcastId,
                'title' => 'GARA-GARA NGONTEN BARENG GOFAR JADWAL CERAMAH USTADZ JOJO JADI PADAT TERUS',
                'description' => 'Ustadz Jojo cerita bagaimana konten bareng Gofar Hilman bikin jadwal ceramahnya jadi padat. Obrolan seru di PWK!',
                'audio_url' => 'https://res.cloudinary.com/dkqwi4lk9/video/upload/v1772700398/PWK_GARA-GARA_NGONTEN_BARENG_GOFAR_JADWAL_CERAMAH_USTADZ_JOJO_JADI_PADAT_TERUS___D8S7d9g8hjA_daporg.mp3',
                'duration_ms' => 3900000,
                'stream_count' => rand(700000, 1800000),
                'release_date' => $now->copy()->subDays(28),
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // Kejar Setoran Episodes
            [
                'id' => Str::uuid()->toString(),
                'podcast_id' => $kejarSetoranId,
                'title' => 'PRAZ TEGUH DAN ABDUR ARSYAD SAMA-SAMA PERNAH DIKECEWAKAN ORANG BARU',
                'description' => 'Praz Teguh dan Abdur Arsyad berbagi cerita soal pengalaman dikecewakan oleh orang baru di kehidupan mereka.',
                'audio_url' => 'https://res.cloudinary.com/dkqwi4lk9/video/upload/v1772700406/TITIK_KUMPUL_-_PRAZ_TEGUH_DAN_ABDUR_ARSYAD_SAMA-SAMA_PERNAH_DIKECEWAKAN_ORANG_BARU__qBxbDAWTaR0_xixy5v.mp3',
                'duration_ms' => 3300000,
                'stream_count' => rand(500000, 1500000),
                'release_date' => $now->copy()->subDays(7),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => Str::uuid()->toString(),
                'podcast_id' => $kejarSetoranId,
                'title' => 'COKI MAKIN ANDAL DALAM MENYAPU OMONGANNYA',
                'description' => 'Coki Pardede makin jago menyapu omongannya sendiri! Obrolan penuh tawa di Kejar Setoran.',
                'audio_url' => 'https://res.cloudinary.com/dkqwi4lk9/video/upload/v1772700402/KEJAR_SETORAN_-_COKI_MAKIN_ANDAL_DALAM_MENYAPU_OMONGANNYA___qGT2wERFWOU_uogdqd.mp3',
                'duration_ms' => 3600000,
                'stream_count' => rand(600000, 1800000),
                'release_date' => $now->copy()->subDays(3),
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    private function generateSyncedLyrics(string $content, int $durationSeconds): array
    {
        $lines = array_values(array_filter(explode("\n", $content), fn($l) => trim($l) !== ''));
        $count = count($lines);

        if ($count === 0) return [];

        $interval = $durationSeconds / ($count + 1);
        $synced = [];

        foreach ($lines as $i => $line) {
            $synced[] = [
                'time' => round(($i + 1) * $interval, 2),
                'text' => trim($line),
            ];
        }

        return $synced;
    }
}