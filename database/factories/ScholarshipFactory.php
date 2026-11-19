<?php

namespace Database\Factories;

use App\Models\Scholarship;
use App\Models\TrainingCenter;
use App\Models\TrainingProgram;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Scholarship>
 */
class ScholarshipFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $center = TrainingCenter::factory()->create(['type' => fake()->randomElement(['PTC', 'PSAT'])]);
        $program = TrainingProgram::factory()->for($center, 'center')->create();

        return [
            'title' => fake()->sentence(4),
            'provider' => fake()->company(),
            'description' => fake()->paragraph(),
            'status' => 'published',
            'verified_at' => now(),
            'training_center_id' => $center->id,
            'training_program_id' => $program->id,
            'slot_limit' => fake()->numberBetween(1, 20),
        ];
    }
}
