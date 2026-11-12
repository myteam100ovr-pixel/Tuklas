<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\TrainingAssignment;
use App\Models\TrainingProgram;
use App\Models\User;
use App\Services\TesdaQualificationCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrainerTeachingAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_qualification_trainer_is_visible_to_all_viewers(): void
    {
        $qualification = collect(app(TesdaQualificationCatalog::class)->all()['qualifications'])->firstWhere('status', 'current');
        $trainer = User::factory()->create(['role' => Role::TesdaAdmin, 'name' => 'Trainer Unique Name', 'is_active' => true]);
        $trainer->trainerQualifications()->create(['qualification_id' => $qualification['id'], 'title' => $qualification['title'], 'nc_level' => $qualification['nc_level']]);

        $this->get(route('tesda.index'))->assertOk()->assertSee('Trainer Unique Name');
        foreach ([Role::Youth, Role::TesdaAdmin, Role::Admin] as $role) {
            $viewer = User::factory()->create(['role' => $role, 'is_active' => true]);
            $this->actingAs($viewer)->get(route('tesda.index'))->assertOk()->assertSee('Trainer Unique Name');
        }
    }

    public function test_youth_can_see_the_assigned_trainer_hours_and_schedule(): void
    {
        $trainer = User::factory()->create(['role' => Role::TesdaAdmin, 'name' => 'Course Instructor']);
        $program = TrainingProgram::factory()->create(['duration_hours' => 120, 'schedule_note' => 'Saturday mornings']);
        TrainingAssignment::factory()->create(['user_id' => $trainer->id, 'training_program_id' => $program->id]);
        $youth = User::factory()->create(['role' => Role::Youth, 'is_active' => true]);

        $this->actingAs($youth)->get(route('tesda.index'))
            ->assertOk()->assertSee('Course Instructor')->assertSee('120 training hours')->assertSee('Saturday mornings');
    }

    public function test_trainer_can_choose_and_remove_a_published_course_to_teach(): void
    {
        $trainer = User::factory()->create(['role' => Role::TesdaAdmin, 'is_active' => true]);
        $program = TrainingProgram::factory()->create();

        $this->actingAs($trainer)->post(route('trainer.teaching.store', $program))->assertRedirect();
        $this->post(route('trainer.teaching.store', $program))->assertRedirect();

        $this->assertDatabaseCount('training_assignments', 1);
        $this->get(route('trainer.programs.index'))->assertSee($program->title);
        $this->get(route('tesda.index'))->assertSee('On your teaching list');

        $this->delete(route('trainer.teaching.destroy', $program))->assertRedirect();
        $this->assertDatabaseCount('training_assignments', 0);
    }

    public function test_only_trainers_can_choose_published_courses(): void
    {
        $draft = TrainingProgram::factory()->create(['status' => 'draft']);
        $trainer = User::factory()->create(['role' => Role::TesdaAdmin, 'is_active' => true]);
        $this->actingAs($trainer)->post(route('trainer.teaching.store', $draft))->assertNotFound();

        $published = TrainingProgram::factory()->create();
        $youth = User::factory()->create(['role' => Role::Youth, 'is_active' => true]);
        $this->actingAs($youth)->post(route('trainer.teaching.store', $published))->assertForbidden();
        $this->assertDatabaseCount('training_assignments', 0);
    }

    public function test_trainer_cannot_remove_another_trainers_teaching_choice(): void
    {
        $owner = User::factory()->create(['role' => Role::TesdaAdmin, 'is_active' => true]);
        $other = User::factory()->create(['role' => Role::TesdaAdmin, 'is_active' => true]);
        $program = TrainingProgram::factory()->create();
        TrainingAssignment::factory()->create(['user_id' => $owner->id, 'training_program_id' => $program->id]);

        $this->actingAs($other)->delete(route('trainer.teaching.destroy', $program))->assertNotFound();
        $this->assertDatabaseHas('training_assignments', ['user_id' => $owner->id]);
    }

    public function test_trainer_can_choose_a_current_national_qualification_to_teach(): void
    {
        $trainer = User::factory()->create(['role' => Role::TesdaAdmin, 'is_active' => true]);
        $qualification = collect(app(TesdaQualificationCatalog::class)->all()['qualifications'])
            ->firstWhere('status', 'current');

        $this->actingAs($trainer)->post(route('trainer.qualifications.store'), [
            'qualification_id' => $qualification['id'],
        ])->assertRedirect();

        $this->assertDatabaseHas('trainer_qualifications', [
            'user_id' => $trainer->id,
            'qualification_id' => $qualification['id'],
            'title' => $qualification['title'],
        ]);
        $this->get(route('trainer.programs.index'))->assertSee($qualification['title']);

        $this->delete(route('trainer.qualifications.destroy', $qualification['id']))->assertRedirect();
        $this->assertDatabaseCount('trainer_qualifications', 0);
    }

    public function test_youth_and_archived_qualifications_cannot_be_chosen_for_teaching(): void
    {
        $catalog = collect(app(TesdaQualificationCatalog::class)->all()['qualifications']);
        $trainer = User::factory()->create(['role' => Role::TesdaAdmin, 'is_active' => true]);
        $archived = $catalog->firstWhere('status', 'archived');
        $this->actingAs($trainer)->post(route('trainer.qualifications.store'), [
            'qualification_id' => $archived['id'],
        ])->assertSessionHasErrors('qualification_id');

        $youth = User::factory()->create(['role' => Role::Youth, 'is_active' => true]);
        $current = $catalog->firstWhere('status', 'current');
        $this->actingAs($youth)->post(route('trainer.qualifications.store'), [
            'qualification_id' => $current['id'],
        ])->assertForbidden();
    }
}
