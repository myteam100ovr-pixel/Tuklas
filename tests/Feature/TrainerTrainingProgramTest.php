<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\TrainingProgram;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrainerTrainingProgramTest extends TestCase
{
    use RefreshDatabase;

    public function test_trainer_can_publish_and_update_an_offered_course(): void
    {
        $trainer = User::factory()->create(['role' => Role::Trainer, 'is_active' => true]);

        $this->actingAs($trainer)->post(route('trainer.programs.store'), [
            'title' => 'Community Baking Workshop NC II',
            'nc_level' => 'NC II',
            'duration_hours' => 280,
            'status' => 'published',
        ])->assertRedirect(route('trainer.programs.index'));

        $program = TrainingProgram::query()->where('title', 'Community Baking Workshop NC II')->sole();
        $this->assertSame($trainer->id, $program->created_by);
        $this->assertSame('published', $program->status);
        $this->get(route('tesda.index'))->assertSee('Community Baking Workshop NC II');

        $this->put(route('trainer.programs.update', $program), [
            'title' => 'Community Baking Workshop NC II',
            'nc_level' => 'NC II',
            'status' => 'archived',
        ])->assertRedirect(route('trainer.programs.index'));

        $this->assertSame('archived', $program->fresh()->status);
        $this->get(route('tesda.index'))->assertDontSee('Community Baking Workshop NC II');
    }

    public function test_trainer_cannot_edit_another_trainers_course(): void
    {
        $owner = User::factory()->create(['role' => Role::Trainer, 'is_active' => true]);
        $otherTrainer = User::factory()->create(['role' => Role::Trainer, 'is_active' => true]);
        $program = TrainingProgram::factory()->create(['created_by' => $owner->id]);

        $this->actingAs($otherTrainer)->get(route('trainer.programs.edit', $program))->assertForbidden();
        $this->put(route('trainer.programs.update', $program), ['title' => 'Changed', 'status' => 'published'])->assertForbidden();

        $this->assertNotSame('Changed', $program->fresh()->title);
    }

    public function test_youth_cannot_manage_courses_and_invalid_course_is_not_saved(): void
    {
        $youth = User::factory()->create(['role' => Role::Youth, 'is_active' => true]);
        $this->actingAs($youth)->post(route('trainer.programs.store'), ['title' => 'Course', 'status' => 'published'])->assertForbidden();

        $trainer = User::factory()->create(['role' => Role::Trainer, 'is_active' => true]);
        $this->actingAs($trainer)->post(route('trainer.programs.store'), ['title' => '', 'nc_level' => 'NC V', 'status' => 'published'])
            ->assertSessionHasErrors(['title', 'nc_level']);

        $this->assertDatabaseCount('training_programs', 0);
    }
}
