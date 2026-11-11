<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Scholarship;
use App\Models\ScholarshipApplication;
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
        $this->assertSame($careerSeeker->id, $application->user_id);
        $this->assertCount(1, $application->documents);
        Storage::disk('local')->assertExists($application->documents[0]['path']);
    }

    public function test_career_seeker_can_only_view_their_own_scholarship_submissions(): void
    {
        $owner = User::factory()->create(['role' => Role::Youth, 'is_active' => true]);
        $otherCareerSeeker = User::factory()->create(['role' => Role::Youth, 'is_active' => true]);
        $application = ScholarshipApplication::query()->create([
            'scholarship_id' => Scholarship::factory()->create()->id,
            'user_id' => $owner->id,
            'application_message' => 'A fictional application used in a permission test.',
        ]);

        $this->actingAs($otherCareerSeeker)
            ->get(route('scholarship-applications.documents', [$application, 0]))
            ->assertForbidden();
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

    public function test_authorized_staff_can_review_a_submitted_scholarship_application(): void
    {
        $staff = User::factory()->create(['role' => Role::SuperAdmin, 'is_active' => true]);
        $applicant = User::factory()->create(['role' => Role::Youth, 'is_active' => true]);
        $application = ScholarshipApplication::query()->create([
            'scholarship_id' => Scholarship::factory()->create()->id,
            'user_id' => $applicant->id,
            'application_message' => 'A fictional application used in a review test.',
        ]);

        $this->actingAs($staff)->put(route('scholarship-applications.update', $application), [
            'status' => 'approved',
            'review_notes' => 'The submitted documents are complete.',
        ])->assertRedirect();

        $this->assertDatabaseHas('scholarship_applications', [
            'id' => $application->id,
            'status' => 'approved',
            'reviewed_by' => $staff->id,
            'review_notes' => 'The submitted documents are complete.',
        ]);
    }

    public function test_career_seeker_can_submit_documents_from_the_mobile_api(): void
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
        ]);
    }

    public function test_other_roles_cannot_submit_or_review_scholarship_applications(): void
    {
        $trainer = User::factory()->create(['role' => Role::Trainer, 'is_active' => true]);
        $scholarship = Scholarship::factory()->create();
        $applicant = User::factory()->create(['role' => Role::Youth, 'is_active' => true]);
        $application = ScholarshipApplication::query()->create([
            'scholarship_id' => Scholarship::factory()->create()->id,
            'user_id' => $applicant->id,
            'application_message' => 'A fictional application used in a permission test.',
        ]);

        $this->actingAs($trainer)->post(route('scholarship-applications.store', $scholarship), [
            'application_message' => 'I would like to apply.',
        ])->assertForbidden();

        $this->actingAs($trainer)->put(route('scholarship-applications.update', $application), [
            'status' => 'approved',
        ])->assertForbidden();
    }
}
