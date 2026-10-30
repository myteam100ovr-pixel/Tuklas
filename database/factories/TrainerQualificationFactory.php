<?php

namespace Database\Factories;

use App\Models\TrainerQualification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrainerQualification>
 */
class TrainerQualificationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'qualification_id' => fake()->unique()->bothify('QUAL-####'),
            'title' => fake()->words(3, true),
            'nc_level' => 'NC II',
        ];
    }
}
