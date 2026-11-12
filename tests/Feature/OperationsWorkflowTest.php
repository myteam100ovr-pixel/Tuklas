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
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OperationsWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_center_representative_sees_only_own_sessions_and_cannot_edit_another_center(): void
    {
        $ownCenter = TrainingCenter::factory()->create();
        $otherCenter = TrainingCenter::factory()->create();
        $representative = User::factory()->create(['role' => Role::TesdaAdmin, 'is_active' => true, 'training_center_id' => $ownCenter->id]);
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
        $representative = User::factory()->create(['role' => Role::TesdaAdmin, 'is_active' => true, 'training_center_id' => $center->id]);

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

    public function test_scholarship_form_keeps_entered_values_after_validation_fails(): void
    {
        $admin = User::factory()->create(['role' => Role::TesdaAdmin, 'is_active' => true]);
        $this->actingAs($admin);

        $this->from(route('scholarships.index'))->post(route('scholarships.store'), [
            '_operation' => 'create-scholarship',
            'title' => 'Local training grant',
            'provider' => 'Pangasinan Skills Office',
            'description' => '',
            'status' => 'published',
        ])->assertRedirect(route('scholarships.index'))->assertSessionHasErrors(['description']);

        $this->get(route('scholarships.index'))->assertOk()
            ->assertSee('value="Local training grant"', false)
            ->assertSee('value="Pangasinan Skills Office"', false)
            ->assertSee('value="published" selected', false);
    }

    public function test_peso_can_publish_a_verified_job_with_the_employer_entered_on_the_opening(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin, 'is_active' => true]);

        $response = $this->actingAs($admin)->post('/admin/opportunities', [
            'employer_name' => 'Northstar Works',
            'employer_verified' => '1',
            'title' => 'Electrical Technician',
            'location' => 'Bugallon, Pangasinan',
            'description' => 'Maintain electrical systems.',
            'status' => 'published',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('job_opportunities', [
            'employer_name' => 'Northstar Works',
            'employer_verified' => true,
            'title' => 'Electrical Technician',
            'posted_by' => $admin->id,
        ]);
    }

    public function test_peso_cannot_publish_a_job_without_confirming_employer_details(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin, 'is_active' => true]);

        $this->actingAs($admin)->from('/opportunities')->post('/admin/opportunities', [
            'employer_name' => 'Northstar Works',
            'title' => 'Electrical Technician',
            'description' => 'Maintain electrical systems.',
            'status' => 'published',
        ])->assertRedirect('/opportunities')->assertSessionHasErrors('employer_verified');

        $this->assertDatabaseMissing('job_opportunities', ['title' => 'Electrical Technician']);
    }

    public function test_peso_marks_a_recommended_career_seeker_hired_and_notifies_only_that_seeker(): void
    {
        config()->set('mail.default', 'array');
        $admin = User::factory()->create(['role' => Role::Admin, 'is_active' => true]);
        $seeker = User::factory()->create(['role' => Role::Youth, 'is_active' => true]);
        $other = User::factory()->create(['role' => Role::Youth, 'is_active' => true]);
        $trainer = User::factory()->create(['role' => Role::TesdaAdmin, 'is_active' => true]);
        $job = JobOpportunity::factory()->create(['title' => 'Electrical Technician', 'employer_name' => 'Northstar Works']);
        $job->recommendedUsers()->attach($seeker->id, ['recommended_by' => $admin->id]);

        $this->actingAs($admin)->post(route('admin.opportunities.hire', $job), ['youth_email' => $seeker->email])
            ->assertRedirect()->assertSessionHas('status', 'Hiring recorded and the career seeker notified by email and in the app.');

        $this->assertDatabaseHas('job_recommendations', ['job_opportunity_id' => $job->id, 'user_id' => $seeker->id, 'hired_by' => $admin->id]);
        $this->assertNotNull($job->recommendedUsers()->whereKey($seeker->id)->firstOrFail()->pivot->hired_at);
        $this->assertSame('hiring', $seeker->notifications()->sole()->data['kind']);
        $this->assertSame(0, $other->notifications()->count());
        $this->assertSame(0, $trainer->notifications()->count());
        $this->assertSame($seeker->email, Mail::mailer('array')->getSymfonyTransport()->messages()->sole()->getEnvelope()->getRecipients()[0]->getAddress());
        $this->actingAs($seeker)->get(route('opportunities.index'))->assertOk()->assertSee('data-my-hired >Hired', false);
        $this->getJson('/api/mobile/opportunities')->assertJsonPath('jobs.data.0.recommended_users.0.pivot.hired_by', $admin->id);
        $this->actingAs($other)->get(route('opportunities.index'))->assertDontSee('data-my-hired >Hired', false);
        $this->getJson('/api/mobile/opportunities')->assertJsonCount(0, 'jobs.data.0.recommended_users');
        $this->actingAs($trainer)->get(route('opportunities.index'))->assertDontSee('Career seeker matches');
        $this->getJson('/api/mobile/opportunities')->assertJsonMissingPath('jobs.data.0.recommended_users');

        $job->update(['status' => 'closed']);
        $this->actingAs($seeker)->getJson('/api/mobile/opportunities')->assertJsonPath('jobs.data.0.id', $job->id);
        $this->actingAs($other)->getJson('/api/mobile/opportunities')->assertJsonCount(0, 'jobs.data');
    }

    public function test_hiring_requires_a_recommendation_and_does_not_send_duplicate_notices(): void
    {
        config()->set('mail.default', 'array');
        $admin = User::factory()->create(['role' => Role::Admin, 'is_active' => true]);
        $seeker = User::factory()->create(['role' => Role::Youth, 'is_active' => true]);
        $trainer = User::factory()->create(['role' => Role::TesdaAdmin, 'is_active' => true]);
        $job = JobOpportunity::factory()->create();

        $this->actingAs($admin)->post(route('admin.opportunities.hire', $job), ['youth_email' => $seeker->email])->assertNotFound();
        $job->recommendedUsers()->attach($seeker->id, ['recommended_by' => $admin->id]);
        $this->actingAs($trainer)->post(route('admin.opportunities.hire', $job), ['youth_email' => $seeker->email])->assertForbidden();
        $this->actingAs($admin)->post(route('admin.opportunities.hire', $job), ['youth_email' => $seeker->email])->assertRedirect();
        $this->post(route('admin.opportunities.hire', $job), ['youth_email' => $seeker->email])->assertSessionHas('status', 'This career seeker is already marked as hired for this opening.');
        $this->postJson('/api/mobile/opportunities/'.$job->id.'/hire', ['youth_email' => $seeker->email])
            ->assertOk()->assertJsonPath('message', 'This career seeker is already marked as hired for this opening.');
        $this->assertSame(1, $seeker->notifications()->count());
        $this->assertSame(1, Mail::mailer('array')->getSymfonyTransport()->messages()->count());
    }

    public function test_hiring_remains_recorded_and_in_app_when_email_delivery_fails(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin, 'is_active' => true]);
        $seeker = User::factory()->create(['role' => Role::Youth, 'is_active' => true]);
        $job = JobOpportunity::factory()->create();
        $job->recommendedUsers()->attach($seeker->id, ['recommended_by' => $admin->id]);
        Mail::shouldReceive('mailer')->once()->andThrow(new \RuntimeException('Mail unavailable'));

        $this->actingAs($admin)->postJson('/api/mobile/opportunities/'.$job->id.'/hire', ['youth_email' => $seeker->email])
            ->assertOk()->assertJsonPath('email_sent', false);

        $this->assertNotNull($job->recommendedUsers()->whereKey($seeker->id)->firstOrFail()->pivot->hired_at);
        $this->assertSame('hiring', $seeker->notifications()->sole()->data['kind']);
    }

    public function test_center_scoped_tesda_can_assign_staff_only_to_their_centers_programs(): void
    {
        $center = TrainingCenter::factory()->create();
        $otherCenter = TrainingCenter::factory()->create();
        $representative = User::factory()->create(['role' => Role::TesdaAdmin, 'is_active' => true, 'training_center_id' => $center->id]);
        $trainer = User::factory()->create(['role' => Role::TesdaAdmin, 'is_active' => true, 'training_center_id' => $center->id]);
        $otherTrainer = User::factory()->create(['role' => Role::TesdaAdmin, 'is_active' => true, 'training_center_id' => $otherCenter->id]);
        $tesdaUser = User::factory()->create(['role' => Role::TesdaAdmin, 'is_active' => true]);
        $program = TrainingProgram::factory()->create(['training_center_id' => $center->id]);
        $otherProgram = TrainingProgram::factory()->create(['training_center_id' => $otherCenter->id]);
        Sanctum::actingAs($representative);

        $this->getJson('/api/mobile/me')->assertOk()
            ->assertJsonPath('user.role', Role::TesdaAdmin->value)
            ->assertJsonPath('user.training_center_id', $center->id);

        $this->postJson('/api/mobile/trainer/programs', ['title' => 'New course', 'status' => 'published'])->assertForbidden();
        $this->putJson('/api/mobile/trainer/programs/'.$program->id, ['title' => $program->title, 'status' => 'draft'])->assertForbidden();

        $this->getJson('/api/mobile/center-trainers')->assertOk()
            ->assertJsonCount(2, 'trainers')
            ->assertJsonFragment(['id' => $tesdaUser->id, 'role' => Role::TesdaAdmin->value]);
        $this->postJson('/api/mobile/center-trainers', ['user_id' => $trainer->id, 'training_program_id' => $program->id])->assertCreated();
        $this->postJson('/api/mobile/center-trainers', ['user_id' => $otherTrainer->id, 'training_program_id' => $program->id])->assertUnprocessable();
        $this->postJson('/api/mobile/center-trainers', ['user_id' => $tesdaUser->id, 'training_program_id' => $program->id])->assertCreated();
        $this->postJson('/api/mobile/center-trainers', ['user_id' => $tesdaUser->id, 'training_program_id' => $otherProgram->id])->assertForbidden();
        $this->getJson('/api/mobile/center-trainers')->assertOk()->assertJsonCount(2, 'assignments.data');
        $tesdaAssignment = TrainingAssignment::query()->where('user_id', $tesdaUser->id)->firstOrFail();
        $this->deleteJson('/api/mobile/center-trainers/'.$tesdaAssignment->id)->assertOk();
        $this->assertDatabaseCount('training_assignments', 0);
    }
}
