<?php

namespace Database\Factories;

use App\Enums\Role;
use App\Models\JobApplication;
use App\Models\JobOpportunity;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JobApplication>
 */
class JobApplicationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'job_opportunity_id' => JobOpportunity::factory(),
            'user_id' => User::factory()->state(['role' => Role::Youth->value, 'is_active' => true]),
            'qualification_summary' => fake()->paragraphs(2, true),
            'application_message' => fake()->optional()->sentence(),
            'status' => 'submitted',
        ];
    }
}
