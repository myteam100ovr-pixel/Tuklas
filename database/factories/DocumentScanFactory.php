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
            'doc_type' => fake()->randomElement(['resume', 'certificate']),
            'original_name' => fake()->word().'.pdf',
            'stored_path' => 'document-scans/'.fake()->uuid().'.pdf',
            'mime' => 'application/pdf',
            'size' => fake()->numberBetween(100_000, 2_000_000),
            'status' => 'done',
            'progress' => 100,
            'result' => ['summary' => fake()->sentence()],
            'error' => null,
            'processed_at' => now(),
        ];
    }
}
