<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\TrainingProgram;
use App\Models\User;
use Database\Seeders\TesdaRegisteredTrainingProgramSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TesdaRegisteredTrainingProgramSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_publishes_fifty_source_linked_qualifications_and_preserves_staff_edits(): void
    {
        $this->seed(TesdaRegisteredTrainingProgramSeeder::class);

        $this->assertDatabaseCount('training_programs', 50);
        $this->assertDatabaseHas('training_programs', [
            'title' => 'Bookkeeping NC III',
            'status' => 'published',
            'requirements' => 'Not specified by provider.',
            'source_reference' => 'https://www.tesda.gov.ph/Tvi/Result',
            'last_verified_at' => '2026-11-11 00:00:00',
        ]);

        $program = TrainingProgram::query()->where('title', 'Bookkeeping NC III')->firstOrFail();
        $this->assertStringContainsString('Registered provider:', $program->description);
        $program->update(['description' => 'TESDA directory details reviewed by staff.']);

        $this->seed(TesdaRegisteredTrainingProgramSeeder::class);

        $this->assertDatabaseCount('training_programs', 50);
        $this->assertSame('published', $program->fresh()->status);
        $this->assertSame('TESDA directory details reviewed by staff.', $program->fresh()->description);

        $youth = User::factory()->create(['role' => Role::Youth, 'is_active' => true]);
        $this->actingAs($youth)->get(route('tesda.index'))
            ->assertSee('Bookkeeping NC III')
            ->assertSee('Registered provider:')
            ->assertSee('does not confirm current schedules or available slots');
    }
}
