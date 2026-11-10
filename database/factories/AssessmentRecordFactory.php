<?php

namespace Database\Factories;

use App\Models\AssessmentRecord;
use App\Models\TrainingApplication;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssessmentRecord>
 */
class AssessmentRecordFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'training_application_id' => TrainingApplication::factory(),
            'scheduled_at' => now()->addWeeks(2),
            'status' => 'scheduled',
            'recorded_by' => User::factory(),
        ];
    }
}
