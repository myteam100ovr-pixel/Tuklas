<?php

namespace Database\Factories;

use App\Models\DocumentScan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentScan>
 */
class DocumentScanFactory extends Factory
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
            'document_type' => fake()->randomElement(['resume', 'certification']),
            'original_name' => fake()->word().'.pdf',
            'file_path' => 'document-scans/'.fake()->uuid().'.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => fake()->numberBetween(100_000, 2_000_000),
            'status' => 'completed',
            'analysis' => ['summary' => fake()->sentence()],
            'failure_message' => null,
            'processed_at' => now(),
        ];
    }
}
