<?php

namespace Database\Factories;

use App\Models\Playlist;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlaylistFactory extends Factory
{
    protected $model = Playlist::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(rand(2, 4), true),
            'description' => fake()->optional(0.8)->sentence(),
            'cover_url' => 'https://picsum.photos/seed/playlist' . fake()->unique()->numerify('###') . '/300/300',
            'is_ai_generated' => false,
            'is_public' => fake()->boolean(80),
        ];
    }
}
