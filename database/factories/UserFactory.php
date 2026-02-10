<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;
    protected static ?string $password = null;

    public function definition(): array
    {
        $username = fake()->unique()->userName();
        return [
            'email' => fake()->unique()->safeEmail(),
            'username' => $username,
            'password_hash' => static::$password ??= Hash::make('password'),
            'full_name' => fake()->name(),
            'profile_image_url' => 'https://picsum.photos/seed/' . $username . '/300/300',
            'date_of_birth' => fake()->dateTimeBetween('-40 years', '-18 years')->format('Y-m-d'),
            'gender' => fake()->randomElement(['Male', 'Female']),
            'is_active' => true,
        ];
    }
}
