<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Scholarship;
use App\Models\ScholarshipApplication;
use App\Models\TrainingAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ScholarshipApplicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_career_seeker_can_submit_a_scholarship_application_with_private_documents(): void
    {
        Storage::fake('local');
        $careerSeeker = User::factory()->create(['role' => Role::Youth, 'is_active' => true]);
        $scholarship = Scholarship::factory()->create();

        $this->actingAs($careerSeeker)->post(route('scholarship-applications.store', $scholarship), [
            'application_message' => 'I am applying to develop skills for local employment.',
            'documents' => [UploadedFile::fake()->create('school-record.pdf', 50, 'application/pdf')],
        ])->assertRedirect();

        $application = ScholarshipApplication::query()->firstOrFail();
        $this->assertSame('submitted', $application->status);
        $this->assertSame('pending', $application->center_approval_status);
        $this->assertSame('pending', $application->trainer_approval_status);
        $this->assertSame($careerSeeker->id, $application->user_id);
        $this->assertCount(1, $application->documents);
        Storage::disk('local')->assertExists($application->documents[0]['path']);
    }

    public function test_career_seeker_can_only_view_their_own_scholarship_documents(): void
    {
        $owner = User::factory()->create(['role' => Role::Youth, 'is_active' => true]);
        $otherCareerSeeker = User::factory()->create(['role' => Role::Youth, 'is_active' => true]);
        $application = ScholarshipApplication::query()->create([
            'scholarship_id' => Scholarship::factory()->create()->id,
            'user_id' => $owner->id,
            'application_message' => 'A fictional application used in a permission test.',
        ]);

        $this->actingAs($otherCareerSeeker)->get(route('scholarship-applications.documents', [$application, 0]))->assertForbidden();
    }

    public function test_application_requires_at_least_one_supporting_document(): void
    {
        $careerSeeker = User::factory()->create(['role' => Role::Youth, 'is_active' => true]);
        $scholarship = Scholarship::factory()->create();

        $this->actingAs($careerSeeker)->post(route('scholarship-applications.store', $scholarship), [
            'application_message' => 'I am applying to develop skills for local employment.',
        ])->assertSessionHasErrors('documents');
        $this->assertDatabaseCount('scholarship_applications', 0);
    }

    public function test_center_and_distinct_assigned_trainer_approve_in_order(): void
    {
        Storage::fake('local');
        $scholarship = Scholarship::factory()->create();
        $centerApprover = User::factory()->create([
            'role' => Role::TesdaAdmin,
            'training_center_id' => $scholarship->training_center_id,
            'is_active' => true,
        ]);
        $trainerApprover = User::factory()->create([
            'role' => Role::TesdaAdmin,
            'training_center_id' => $scholarship->training_center_id,
            'is_active' => true,
        ]);
        TrainingAssignment::query()->create([
            'training_program_id' => $scholarship->training_program_id,
            'user_id' => $trainerApprover->id,
        ]);
        $applicant = User::factory()->create(['role' => Role::Youth, 'is_active' => true]);
        $documentPath = 'scholarship-applications/'.$applicant->id.'/supporting-document.pdf';
        Storage::disk('local')->put($documentPath, 'Available supporting document.');
        $application = ScholarshipApplication::query()->create([
            'scholarship_id' => $scholarship->id,
            'user_id' => $applicant->id,
            'application_message' => 'A fictional application used in a review test.',
            'documents' => [['name' => 'supporting-document.pdf', 'path' => $documentPath]],
            'center_approval_status' => 'pending',
            'trainer_approval_status' => 'pending',
        ]);

        $this->actingAs($centerApprover)->put(route('scholarship-applications.update', $application), [
            'stage' => 'center', 'decision' => 'approved',
        ])->assertRedirect();
        $this->assertDatabaseHas('scholarship_applications', [
            'id' => $application->id,
            'status' => 'reviewing',
            'center_approval_status' => 'approved',
            'center_approved_by' => $centerApprover->id,
        ]);

        $this->actingAs($centerApprover)->put(route('scholarship-applications.update', $application), [
            'stage' => 'trainer', 'decision' => 'approved',
        ])->assertForbidden();
        $this->actingAs($trainerApprover)->put(route('scholarship-applications.update', $application), [
            'stage' => 'trainer', 'decision' => 'approved',
        ])->assertRedirect();
        $this->assertDatabaseHas('scholarship_applications', [
            'id' => $application->id,
            'status' => 'approved',
            'trainer_approval_status' => 'approved',
            'trainer_approved_by' => $trainerApprover->id,
            'review_notes' => null,
        ]);
    }

    public function test_center_cannot_approve_an_application_with_unavailable_documents(): void
    {
        Storage::fake('local');
        $scholarship = Scholarship::factory()->create();
        $centerApprover = User::factory()->create([
            'role' => Role::TesdaAdmin,
            'training_center_id' => $scholarship->training_center_id,
            'is_active' => true,
        ]);
        $applicant = User::factory()->create(['role' => Role::Youth, 'is_active' => true]);
        $application = ScholarshipApplication::query()->create([
            'scholarship_id' => $scholarship->id,
            'user_id' => $applicant->id,
            'application_message' => 'A fictional application used in a review test.',
            'documents' => [['name' => 'missing-document.pdf', 'path' => 'scholarship-applications/missing.pdf']],
            'center_approval_status' => 'pending',
            'trainer_approval_status' => 'pending',
        ]);

        $this->actingAs($centerApprover)->from(route('scholarships.index'))->put(route('scholarship-applications.update', $application), [
            'stage' => 'center', 'decision' => 'approved',
        ])->assertRedirect(route('scholarships.index'))->assertSessionHasErrors('documents');

        $this->assertDatabaseHas('scholarship_applications', [
            'id' => $application->id,
            'status' => 'submitted',
            'center_approval_status' => 'pending',
        ]);
    }

    public function test_center_cannot_approve_an_application_with_an_incomplete_message(): void
    {
        Storage::fake('local');
        $scholarship = Scholarship::factory()->create();
        $centerApprover = User::factory()->create([
            'role' => Role::TesdaAdmin,
            'training_center_id' => $scholarship->training_center_id,
            'is_active' => true,
        ]);
        $applicant = User::factory()->create(['role' => Role::Youth, 'is_active' => true]);
        $documentPath = 'scholarship-applications/'.$applicant->id.'/supporting-document.pdf';
        Storage::disk('local')->put($documentPath, 'Available supporting document.');
        $application = ScholarshipApplication::query()->create([
            'scholarship_id' => $scholarship->id,
            'user_id' => $applicant->id,
            'application_message' => 'Too short.',
            'documents' => [['name' => 'supporting-document.pdf', 'path' => $documentPath]],
            'center_approval_status' => 'pending',
            'trainer_approval_status' => 'pending',
        ]);

        $this->actingAs($centerApprover)->from(route('scholarships.index'))->put(route('scholarship-applications.update', $application), [
            'stage' => 'center', 'decision' => 'approved',
        ])->assertRedirect(route('scholarships.index'))->assertSessionHasErrors('documents');

        $this->assertDatabaseHas('scholarship_applications', [
            'id' => $application->id,
            'status' => 'submitted',
            'center_approval_status' => 'pending',
        ]);
    }

    public function test_final_acceptance_cannot_bypass_unavailable_application_documents(): void
    {
        Storage::fake('local');
        $applicant = User::factory()->create(['role' => Role::Youth, 'is_active' => true]);
        $application = ScholarshipApplication::query()->create([
            'scholarship_id' => Scholarship::factory()->create()->id,
            'user_id' => $applicant->id,
            'application_message' => 'A fictional application used in a review test.',
            'documents' => [['name' => 'missing-document.pdf', 'path' => 'scholarship-applications/missing.pdf']],
        ]);
        $admin = User::factory()->create(['role' => Role::Admin, 'is_active' => true]);

        $this->actingAs($admin)->putJson(route('scholarship-applications.update', $application), [
            'stage' => 'final', 'decision' => 'approved',
        ])->assertUnprocessable()->assertJsonValidationErrors('documents');

        $this->assertDatabaseHas('scholarship_applications', [
            'id' => $application->id,
            'status' => 'submitted',
        ]);
    }

    public function test_review_page_disables_acceptance_when_a_supporting_document_is_unavailable(): void
    {
        Storage::fake('local');
        $scholarship = Scholarship::factory()->create([
            'requirements' => 'Certificate of residence and a valid ID.',
        ]);
        $applicant = User::factory()->create(['role' => Role::Youth, 'is_active' => true]);
        ScholarshipApplication::query()->create([
            'scholarship_id' => $scholarship->id,
            'user_id' => $applicant->id,
            'application_message' => 'A fictional application used in a review test.',
            'documents' => [['name' => 'missing-document.pdf', 'path' => 'scholarship-applications/missing.pdf']],
        ]);
        $admin = User::factory()->create(['role' => Role::Admin, 'is_active' => true]);

        $this->actingAs($admin)->get(route('scholarships.index'))
            ->assertOk()
            ->assertSee('Certificate of residence and a valid ID.')
            ->assertSee('<strong>Scholarship requirements:</strong> Certificate of residence and a valid ID.', false)
            ->assertSee('Acceptance is disabled until the application message is complete and every uploaded file is available.')
            ->assertSee('<button type="submit" name="decision" value="approved" disabled>Accept application</button>', false)
            ->assertSee('data-review-decline-open', false)
            ->assertSee('data-review-decline-dialog', false);
    }

    public function test_failed_center_review_releases_the_reserved_slot(): void
    {
        $scholarship = Scholarship::factory()->create(['slot_limit' => 1]);
        $centerApprover = User::factory()->create([
            'role' => Role::TesdaAdmin,
            'training_center_id' => $scholarship->training_center_id,
            'is_active' => true,
        ]);
        $applicant = User::factory()->create(['role' => Role::Youth, 'is_active' => true]);
        $application = ScholarshipApplication::query()->create([
            'scholarship_id' => $scholarship->id,
            'user_id' => $applicant->id,
            'application_message' => 'A fictional application used in a review test.',
            'center_approval_status' => 'pending',
            'trainer_approval_status' => 'pending',
        ]);

        $this->actingAs($centerApprover)->from(route('scholarships.index'))->put(route('scholarship-applications.update', $application), [
            'stage' => 'center', 'decision' => 'failed',
        ])->assertRedirect(route('scholarships.index'))->assertSessionHasErrors('notes');

        $this->actingAs($centerApprover)->put(route('scholarship-applications.update', $application), [
            'stage' => 'center', 'decision' => 'failed', 'notes' => 'A required document was missing.',
        ])->assertRedirect();
        $this->assertDatabaseHas('scholarship_applications', [
            'id' => $application->id,
            'status' => 'rejected',
            'center_approval_status' => 'failed',
            'trainer_approval_status' => 'cancelled',
        ]);

        Storage::fake('local');
        $nextApplicant = User::factory()->create(['role' => Role::Youth, 'is_active' => true]);
        $this->actingAs($nextApplicant)->post(route('scholarship-applications.store', $scholarship), [
            'application_message' => 'I am applying to develop skills for local employment.',
            'documents' => [UploadedFile::fake()->create('requirements.pdf', 50, 'application/pdf')],
        ])->assertRedirect();
    }

    public function test_first_come_slot_limit_blocks_new_applications_and_cancel_releases_a_place(): void
    {
        Storage::fake('local');
        $scholarship = Scholarship::factory()->create(['slot_limit' => 1]);
        $firstApplicant = User::factory()->create(['role' => Role::Youth, 'is_active' => true]);
        $nextApplicant = User::factory()->create(['role' => Role::Youth, 'is_active' => true]);
        $firstSubmission = [
            'application_message' => 'I am applying to develop skills for local employment.',
            'documents' => [UploadedFile::fake()->create('requirements.pdf', 50, 'application/pdf')],
        ];
        $this->actingAs($firstApplicant)->post(route('scholarship-applications.store', $scholarship), $firstSubmission)->assertRedirect();

        $secondSubmission = [
            'application_message' => 'I am applying to develop skills for local employment.',
            'documents' => [UploadedFile::fake()->create('requirements.pdf', 50, 'application/pdf')],
        ];
        $this->actingAs($nextApplicant)->postJson(route('scholarship-applications.store', $scholarship), $secondSubmission)
            ->assertStatus(422)
            ->assertJsonFragment(['message' => 'This scholarship is fully occupied. Please check the other private training centers listed on this page.']);

        $application = ScholarshipApplication::query()->where('user_id', $firstApplicant->id)->firstOrFail();
        $this->actingAs($firstApplicant)->delete(route('scholarship-applications.cancel', $application))->assertRedirect();
        $this->actingAs($nextApplicant)->post(route('scholarship-applications.store', $scholarship), $secondSubmission)->assertRedirect();
        $this->assertDatabaseHas('scholarship_applications', ['id' => $application->id, 'status' => 'cancelled']);
        $this->assertDatabaseHas('scholarship_applications', [
            'scholarship_id' => $scholarship->id, 'user_id' => $nextApplicant->id, 'status' => 'submitted',
        ]);
    }

    public function test_career_seeker_can_submit_documents_from_mobile_api(): void
    {
        $careerSeeker = User::factory()->create(['role' => Role::Youth, 'is_active' => true]);
        $scholarship = Scholarship::factory()->create();

        $this->actingAs($careerSeeker)->post('/api/mobile/scholarships/'.$scholarship->id.'/applications', [
            'application_message' => 'I am applying to pursue a technical training program.',
            'documents' => [UploadedFile::fake()->create('school-record.pdf', 50, 'application/pdf')],
        ])->assertCreated()->assertJsonPath('application.status', 'submitted');
        $this->assertDatabaseHas('scholarship_applications', [
            'scholarship_id' => $scholarship->id,
            'user_id' => $careerSeeker->id,
            'status' => 'submitted',
            'center_approval_status' => 'pending',
            'trainer_approval_status' => 'pending',
        ]);
    }

    public function test_admin_and_unassigned_tesda_accounts_cannot_make_application_decisions(): void
    {
        $applicant = User::factory()->create(['role' => Role::Youth, 'is_active' => true]);
        $application = ScholarshipApplication::query()->create([
            'scholarship_id' => Scholarship::factory()->create()->id,
            'user_id' => $applicant->id,
            'application_message' => 'A fictional application used in a permission test.',
            'center_approval_status' => 'pending',
            'trainer_approval_status' => 'pending',
        ]);

        foreach ([Role::Admin, Role::TesdaAdmin] as $role) {
            $staff = User::factory()->create(['role' => $role, 'is_active' => true]);
            $this->actingAs($staff)->put(route('scholarship-applications.update', $application), [
                'stage' => 'center', 'decision' => 'approved',
            ])->assertForbidden();
        }
    }
}
