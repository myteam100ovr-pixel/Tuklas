<?php

namespace Database\Factories;

use App\Models\Employer;
use App\Models\JobOpportunity;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JobOpportunity>
 */
class JobOpportunityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'employer_id' => Employer::factory(),
            'title' => fake()->jobTitle(),
            'description' => fake()->paragraph(),
            'status' => 'published',
            'posted_by' => User::factory(),
        ];
    }
}
