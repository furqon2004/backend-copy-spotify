<?php 
namespace Database\Factories;

use App\Models\Song;
use App\Models\Artist;
use App\Models\Album;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SongFactory extends Factory
{
    protected $model = Song::class;

    public function definition(): array
    {
        $title = fake()->sentence(3);
        
        return [
            'id' => Str::uuid(),
            'artist_id' => Artist::factory(),
            'album_id' => Album::factory(),
            'title' => $title,
            'slug' => Str::slug($title) . '-' . Str::random(5),
            'cover_url' => 'https://res.cloudinary.com/demo/image/upload/v1234567/sample.jpg',
            'file_path' => 'songs/private/' . Str::random(40) . '.mp3',
            'file_size' => fake()->numberBetween(2000000, 15000000),
            'duration_seconds' => fake()->numberBetween(120, 480),
            'stream_count' => fake()->numberBetween(0, 1000000),
            'is_explicit' => fake()->boolean(10),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}