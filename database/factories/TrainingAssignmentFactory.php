<?php

namespace Database\Factories;

use App\Models\TrainingAssignment;
use App\Models\TrainingProgram;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrainingAssignment>
 */
class TrainingAssignmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'training_program_id' => TrainingProgram::factory(),
            'user_id' => User::factory(),
        ];
    }
}
