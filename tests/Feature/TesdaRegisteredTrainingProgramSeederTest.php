<?php

namespace Tests\Feature;

use App\Models\TrainingProgram;
use Database\Seeders\TesdaRegisteredTrainingProgramSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TesdaRegisteredTrainingProgramSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_imports_fifty_real_source_linked_qualifications_as_drafts_and_is_repeatable(): void
    {
        $this->seed(TesdaRegisteredTrainingProgramSeeder::class);

        $this->assertDatabaseCount('training_programs', 50);
        $this->assertDatabaseHas('training_programs', [
            'title' => 'Bookkeeping NC III',
            'status' => 'draft',
            'requirements' => 'Not specified by provider.',
            'source_reference' => 'https://www.tesda.gov.ph/Tvi/Result',
            'last_verified_at' => null,
        ]);

        $program = TrainingProgram::query()->where('title', 'Bookkeeping NC III')->firstOrFail();
        $this->assertStringContainsString('Registered provider:', $program->description);
        $program->update(['status' => 'published']);

        $this->seed(TesdaRegisteredTrainingProgramSeeder::class);

        $this->assertDatabaseCount('training_programs', 50);
        $this->assertSame('published', $program->fresh()->status);
    }
}
