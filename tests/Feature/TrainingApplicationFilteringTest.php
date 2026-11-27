<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\TrainingApplication;
use App\Models\TrainingCenter;
use App\Models\TrainingProgram;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrainingApplicationFilteringTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_filter_training_applications_by_status_search_center_and_course(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin, 'is_active' => true]);
        $center = TrainingCenter::factory()->create(['name' => 'Amianan Technical Learning Center']);
        $otherCenter = TrainingCenter::factory()->create(['name' => 'Pangasinan Skills Training Center']);
        $electricalCourse = TrainingProgram::factory()->for($center, 'center')->create(['title' => 'Electrical Installation NC II']);
        $pastryCourse = TrainingProgram::factory()->for($center, 'center')->create(['title' => 'Bread and Pastry Production NC II']);
        $otherElectricalCourse = TrainingProgram::factory()->for($otherCenter, 'center')->create(['title' => 'Electrical Installation Advanced']);
        $trainee = User::factory()->create(['name' => 'Maria Dela Cruz']);
        $otherTrainee = User::factory()->create(['name' => 'Ana Santos']);

        TrainingApplication::factory()->for($electricalCourse, 'program')->for($trainee, 'user')->create(['status' => 'pending']);
        TrainingApplication::factory()->for($pastryCourse, 'program')->for($otherTrainee, 'user')->create(['status' => 'pending']);
        TrainingApplication::factory()->for($otherElectricalCourse, 'program')->for(User::factory(), 'user')->create(['status' => 'accepted']);

        $response = $this->actingAs($admin)->get(route('applications.index', [
            'status' => 'pending',
            'q' => 'Maria Dela Cruz',
            'center' => $center->id,
            'course' => $electricalCourse->id,
        ]));

        $response->assertOk()
            ->assertSee('Electrical Installation NC II')
            ->assertSee('aria-current="page"', false)
            ->assertSee('Reason for declining')
            ->assertSee('textarea name="decision_reason" required', false)
            ->assertSee('Reset filters');
        $this->assertSame(1, $response->viewData('applications')->total());
        $this->assertSame([
            'all' => 3,
            'pending' => 2,
            'accepted' => 1,
            'declined' => 0,
        ], $response->viewData('applicationCounts'));

        $courseSearch = $this->get(route('applications.index', ['q' => 'Electrical']))
            ->assertOk()
            ->assertSee('Electrical Installation NC II');
        $this->assertSame(2, $courseSearch->viewData('applications')->total());

        $this->get(route('applications.index', ['status' => 'accepted']))
            ->assertOk()
            ->assertSee('Edit decision')
            ->assertSee('Set as accepted');
    }

    public function test_empty_filter_results_show_a_resettable_empty_state(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin, 'is_active' => true]);

        $this->actingAs($admin)->get(route('applications.index', ['q' => 'No matching trainee']))
            ->assertOk()
            ->assertSee('No training requests match the current filters.')
            ->assertSee('Reset filters');
    }

    public function test_declines_require_a_reason_and_completed_decisions_can_be_changed(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin, 'is_active' => true]);
        $trainee = User::factory()->create(['role' => Role::Youth, 'is_active' => true]);
        $program = TrainingProgram::factory()->create(['title' => 'Electrical Installation NC II']);
        $application = TrainingApplication::factory()->for($program, 'program')->for($trainee, 'user')->create();

        $this->actingAs($admin)->put(route('applications.update', $application), [
            'status' => 'declined',
        ])->assertSessionHasErrors('decision_reason');

        $this->assertDatabaseHas('training_applications', [
            'id' => $application->id,
            'status' => 'pending',
            'decision_reason' => null,
        ]);

        $this->put(route('applications.update', $application), [
            'status' => 'declined',
            'decision_reason' => 'The requested intake is currently full.',
        ])->assertRedirect();

        $this->assertDatabaseHas('training_applications', [
            'id' => $application->id,
            'status' => 'declined',
            'decision_reason' => 'The requested intake is currently full.',
        ]);

        $this->put(route('applications.update', $application), [
            'status' => 'accepted',
        ])->assertRedirect();

        $this->assertDatabaseHas('training_applications', [
            'id' => $application->id,
            'status' => 'accepted',
            'decision_reason' => null,
        ]);

        $this->put(route('applications.update', $application), [
            'status' => 'declined',
            'decision_reason' => 'The trainee is not eligible for this intake.',
        ])->assertRedirect();

        $this->assertDatabaseHas('training_applications', [
            'id' => $application->id,
            'status' => 'declined',
            'decision_reason' => 'The trainee is not eligible for this intake.',
        ]);
    }
}
