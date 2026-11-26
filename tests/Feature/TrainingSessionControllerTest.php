<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\TrainingAssignment;
use App\Models\TrainingCenter;
use App\Models\TrainingProgram;
use App\Models\TrainingSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrainingSessionControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_schedule_form_shows_active_trainers_assigned_to_the_selected_course(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin, 'is_active' => true]);
        $center = TrainingCenter::factory()->create();
        $program = TrainingProgram::factory()->create(['training_center_id' => $center->id, 'duration_days' => 3]);
        $assignedTrainer = User::factory()->create(['name' => 'Assigned TESDA Trainer', 'role' => Role::TesdaAdmin, 'is_active' => true, 'training_center_id' => $center->id]);
        $inactiveTrainer = User::factory()->create(['name' => 'Inactive TESDA Trainer', 'role' => Role::TesdaAdmin, 'is_active' => false, 'training_center_id' => $center->id]);
        TrainingAssignment::factory()->create(['training_program_id' => $program->id, 'user_id' => $assignedTrainer->id]);
        TrainingAssignment::factory()->create(['training_program_id' => $program->id, 'user_id' => $inactiveTrainer->id]);

        $this->actingAs($admin)->get(route('sessions.index'))
            ->assertOk()
            ->assertSee('Assigned TESDA Trainer')
            ->assertDontSee('Inactive TESDA Trainer')
            ->assertSee('data-duration-days="3"', false);
    }

    public function test_session_saves_exact_course_dates_skipping_weekends_and_selected_non_working_days(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin, 'is_active' => true]);
        $center = TrainingCenter::factory()->create();
        $program = TrainingProgram::factory()->create(['training_center_id' => $center->id, 'duration_days' => 3]);
        $trainer = User::factory()->create(['role' => Role::TesdaAdmin, 'is_active' => true, 'training_center_id' => $center->id]);
        TrainingAssignment::factory()->create(['training_program_id' => $program->id, 'user_id' => $trainer->id]);

        $this->actingAs($admin)->from(route('sessions.index'))->post(route('sessions.store'), [
            'training_program_id' => $program->id,
            'training_center_id' => $center->id,
            'trainer_id' => $trainer->id,
            'starts_at' => '2026-11-27T08:00',
            'ends_at' => '2026-11-27T17:00',
            'skip_weekends' => '1',
            'non_working_dates' => ['2026-11-30'],
            'capacity' => 18,
            'status' => 'open',
        ])->assertRedirect(route('sessions.index'))->assertSessionHas('status', 'Training session saved.');

        $session = TrainingSession::query()->sole();
        $this->assertSame(['2026-12-01', '2026-12-02', '2026-12-03'], $session->training_dates);
        $this->assertSame('2026-12-01', $session->starts_at->toDateString());
        $this->assertSame('2026-12-03', $session->ends_at->toDateString());
        $this->assertSame($trainer->id, $session->trainer_id);
        $this->assertTrue($session->skip_weekends);
        $this->assertSame(['2026-11-30'], $session->non_working_dates);
    }

    public function test_session_rejects_a_trainer_not_assigned_to_the_selected_course(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin, 'is_active' => true]);
        $center = TrainingCenter::factory()->create();
        $program = TrainingProgram::factory()->create(['training_center_id' => $center->id, 'duration_days' => 2]);
        $trainer = User::factory()->create(['role' => Role::TesdaAdmin, 'is_active' => true, 'training_center_id' => $center->id]);

        $this->actingAs($admin)->from(route('sessions.index'))->post(route('sessions.store'), [
            'training_program_id' => $program->id,
            'training_center_id' => $center->id,
            'trainer_id' => $trainer->id,
            'starts_at' => '2026-11-27T08:00',
            'ends_at' => '2026-11-27T17:00',
            'capacity' => 18,
            'status' => 'open',
        ])->assertRedirect(route('sessions.index'))->assertSessionHasErrors('trainer_id');

        $this->assertDatabaseCount('training_sessions', 0);
    }

    public function test_session_rejects_a_date_list_that_does_not_match_the_course_duration(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin, 'is_active' => true]);
        $center = TrainingCenter::factory()->create();
        $program = TrainingProgram::factory()->create(['training_center_id' => $center->id, 'duration_days' => 2]);

        $this->actingAs($admin)->from(route('sessions.index'))->post(route('sessions.store'), [
            'training_program_id' => $program->id,
            'training_center_id' => $center->id,
            'starts_at' => '2026-11-27T08:00',
            'ends_at' => '2026-11-27T17:00',
            'training_dates' => ['2026-11-27'],
            'capacity' => 18,
            'status' => 'open',
        ])->assertRedirect(route('sessions.index'))->assertSessionHasErrors('training_dates');

        $this->assertDatabaseCount('training_sessions', 0);
    }
}
