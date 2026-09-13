<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'username' => $this->faker->unique()->userName(),
            'fullname' => strtoupper($this->faker->name()),
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
            // The legacy schema stores these relationships as required IDs.
            // Seeders can replace them with their own fixture IDs.
            'badan_usaha_id' => 1,
            'division_id' => 1,
            'role_id' => 1,
            'approval_id' => 1,
            'remember_token' => Str::random(10),
        ];
    }

}
