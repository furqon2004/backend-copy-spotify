<?php

namespace Database\Factories;

use App\Models\Artist;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ArtistFactory extends Factory
{
    protected $model = Artist::class;

    public function definition(): array
    {
        $name = fake()->name();
        return [
            'name' => $name,
            'slug' => Str::slug($name) . '-' . fake()->unique()->numerify('###'),
            'bio' => fake()->paragraph(3),
            'avatar_url' => 'https://picsum.photos/seed/' . Str::slug($name) . '/300/300',
            'monthly_listeners' => fake()->numberBetween(1000, 5000000),
            'is_verified' => fake()->boolean(70),
        ];
    }
}
