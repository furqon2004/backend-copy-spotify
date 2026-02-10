<?php

namespace Database\Factories;

use App\Models\Album;
use Illuminate\Database\Eloquent\Factories\Factory;

class AlbumFactory extends Factory
{
    protected $model = Album::class;

    public function definition(): array
    {
        return [
            'title' => fake()->words(rand(1, 4), true),
            'release_date' => fake()->dateTimeBetween('-5 years', 'now')->format('Y-m-d'),
            'cover_image_url' => 'https://picsum.photos/seed/album' . fake()->unique()->numerify('###') . '/300/300',
            'type' => fake()->randomElement(['ALBUM', 'SINGLE', 'EP']),
            'total_tracks' => fake()->numberBetween(1, 15),
        ];
    }
}
