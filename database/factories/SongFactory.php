<?php

namespace Database\Factories;

use App\Models\Song;
use Illuminate\Database\Eloquent\Factories\Factory;

class SongFactory extends Factory
{
    protected $model = Song::class;

    public function definition(): array
    {
        return [
            'title' => fake()->words(rand(1, 5), true),
            'duration_ms' => fake()->numberBetween(120000, 360000),
            'file_url' => 'https://res.cloudinary.com/demo/video/upload/sample.mp3',
            'track_number' => fake()->numberBetween(1, 15),
            'stream_count' => fake()->numberBetween(0, 10000000),
            'lyrics' => fake()->optional(0.7)->paragraphs(3, true),
        ];
    }
}
