<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\AssessmentRecord;
use App\Models\Employer;
use App\Models\JobOpportunity;
use App\Models\Scholarship;
use App\Models\TrainingAssignment;
use App\Models\TrainingCenter;
use App\Models\TrainingProgram;
use App\Models\TrainingSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OperationsWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_center_representative_sees_only_own_sessions_and_cannot_edit_another_center(): void
    {
        $ownCenter = TrainingCenter::factory()->create();
        $otherCenter = TrainingCenter::factory()->create();
        $representative = User::factory()->create(['role' => Role::CenterRepresentative, 'is_active' => true, 'training_center_id' => $ownCenter->id]);
        $ownSession = TrainingSession::factory()->create(['training_center_id' => $ownCenter->id]);
        $otherSession = TrainingSession::factory()->create(['training_center_id' => $otherCenter->id]);
        Sanctum::actingAs($representative);

        $this->getJson('/api/mobile/training-sessions')->assertOk()
            ->assertJsonCount(1, 'sessions.data')
            ->assertJsonPath('sessions.data.0.id', $ownSession->id);

        $this->putJson('/api/mobile/training-sessions/'.$otherSession->id, [
            'training_program_id' => $otherSession->training_program_id,
            'training_center_id' => $otherCenter->id,
            'starts_at' => now()->addWeek()->toDateTimeString(),
            'ends_at' => now()->addWeek()->addHours(8)->toDateTimeString(),
            'capacity' => 10,
            'status' => 'open',
        ])->assertForbidden();
    }

    public function test_youth_applies_and_center_accepts_only_until_capacity(): void
    {
        $center = TrainingCenter::factory()->create();
        $session = TrainingSession::factory()->create(['training_center_id' => $center->id, 'capacity' => 1]);
        $firstYouth = User::factory()->create(['role' => Role::Youth, 'is_active' => true]);
        $secondYouth = User::factory()->create(['role' => Role::Youth, 'is_active' => true]);
        $representative = User::factory()->create(['role' => Role::CenterRepresentative, 'is_active' => true, 'training_center_id' => $center->id]);

        Sanctum::actingAs($firstYouth);
        $this->postJson('/api/mobile/training-sessions/'.$session->id.'/apply')->assertCreated();
        $this->postJson('/api/mobile/training-sessions/'.$session->id.'/apply')->assertUnprocessable();

        Sanctum::actingAs($secondYouth);
        $this->postJson('/api/mobile/training-sessions/'.$session->id.'/apply')->assertCreated();

        Sanctum::actingAs($representative);
        $applications = $session->applications()->orderBy('id')->get();
        $this->putJson('/api/mobile/training-applications/'.$applications[0]->id, ['status' => 'accepted'])->assertOk();
        $this->putJson('/api/mobile/training-applications/'.$applications[1]->id, ['status' => 'accepted'])->assertUnprocessable();
        $this->postJson('/api/mobile/assessment-records', [
            'training_application_id' => $applications[0]->id,
            'status' => 'scheduled',
            'scheduled_at' => now()->addDays(10)->toDateTimeString(),
        ])->assertCreated();
        $this->assertSame(1, AssessmentRecord::count());
    }

    public function test_youth_sees_only_published_verified_opportunities_and_scholarships(): void
    {
        $employer = Employer::factory()->create(['is_verified' => true]);
        $job = JobOpportunity::factory()->create(['employer_id' => $employer->id, 'status' => 'published']);
        $draft = JobOpportunity::factory()->create(['employer_id' => $employer->id, 'status' => 'draft']);
        $publishedScholarship = Scholarship::factory()->create(['status' => 'published', 'verified_at' => now()]);
        $draftScholarship = Scholarship::factory()->create(['status' => 'draft']);
        Sanctum::actingAs(User::factory()->create(['role' => Role::Youth, 'is_active' => true]));

        $this->getJson('/api/mobile/opportunities')->assertOk()
            ->assertJsonCount(1, 'jobs.data')
            ->assertJsonPath('jobs.data.0.id', $job->id);
        $this->getJson('/api/mobile/scholarships')->assertOk()
            ->assertJsonCount(1, 'scholarships.data')
            ->assertJsonPath('scholarships.data.0.id', $publishedScholarship->id);
        $this->postJson('/api/mobile/opportunities', [])->assertForbidden();
    }

    public function test_search_finds_relevant_operations_without_exposing_unpublished_records(): void
    {
        $employer = Employer::factory()->create(['name' => 'Northstar Works', 'is_verified' => true]);
        $job = JobOpportunity::factory()->create(['employer_id' => $employer->id, 'title' => 'Electrician']);
        JobOpportunity::factory()->create(['employer_id' => $employer->id, 'title' => 'Secret Planner', 'status' => 'draft']);
        $scholarship = Scholarship::factory()->create(['title' => 'Training Support', 'provider' => 'Northstar Foundation']);
        $center = TrainingCenter::factory()->create(['name' => 'Northstar Center', 'is_active' => true]);
        $program = TrainingProgram::factory()->create(['title' => 'Electrical Installation']);
        $session = TrainingSession::factory()->create(['training_program_id' => $program->id, 'training_center_id' => $center->id]);
        Sanctum::actingAs(User::factory()->create(['role' => Role::Youth, 'is_active' => true]));

        $this->getJson('/api/mobile/opportunities?q=Northstar')->assertOk()
            ->assertJsonCount(1, 'jobs.data')->assertJsonPath('jobs.data.0.id', $job->id);
        $this->getJson('/api/mobile/opportunities?q=Secret')->assertOk()->assertJsonCount(0, 'jobs.data');
        $this->getJson('/api/mobile/scholarships?q=Northstar')->assertOk()
            ->assertJsonCount(1, 'scholarships.data')->assertJsonPath('scholarships.data.0.id', $scholarship->id);
        $this->getJson('/api/mobile/training-centers?q=Northstar')->assertOk()
            ->assertJsonCount(1, 'centers.data')->assertJsonPath('centers.data.0.id', $center->id);
        $this->getJson('/api/mobile/training-sessions?q=Electrical')->assertOk()
            ->assertJsonCount(1, 'sessions.data')->assertJsonPath('sessions.data.0.id', $session->id);
    }

    public function test_representative_can_assign_only_trainers_and_programs_at_their_center(): void
    {
        $center = TrainingCenter::factory()->create();
        $otherCenter = TrainingCenter::factory()->create();
        $representative = User::factory()->create(['role' => Role::CenterRepresentative, 'is_active' => true, 'training_center_id' => $center->id]);
        $trainer = User::factory()->create(['role' => Role::Trainer, 'is_active' => true, 'training_center_id' => $center->id]);
        $otherTrainer = User::factory()->create(['role' => Role::Trainer, 'is_active' => true, 'training_center_id' => $otherCenter->id]);
        $program = TrainingProgram::factory()->create(['training_center_id' => $center->id]);
        Sanctum::actingAs($representative);

        $this->postJson('/api/mobile/trainer/programs', ['title' => 'New course', 'status' => 'published'])->assertForbidden();
        $this->putJson('/api/mobile/trainer/programs/'.$program->id, ['title' => $program->title, 'status' => 'draft'])->assertForbidden();

        $this->postJson('/api/mobile/center-trainers', ['user_id' => $trainer->id, 'training_program_id' => $program->id])->assertCreated();
        $this->postJson('/api/mobile/center-trainers', ['user_id' => $otherTrainer->id, 'training_program_id' => $program->id])->assertUnprocessable();
        $this->getJson('/api/mobile/center-trainers')->assertOk()->assertJsonCount(1, 'assignments.data');
        $assignment = TrainingAssignment::firstOrFail();
        $this->deleteJson('/api/mobile/center-trainers/'.$assignment->id)->assertOk();
        $this->assertDatabaseCount('training_assignments', 0);
    }
}
