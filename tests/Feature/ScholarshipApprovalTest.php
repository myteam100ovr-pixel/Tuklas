<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Events\WorkspaceChanged;
use App\Models\Scholarship;
use App\Models\TrainingAssignment;
use App\Models\TrainingCenter;
use App\Models\TrainingProgram;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ScholarshipApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_listing_becomes_public_only_after_center_and_different_trainer_approve(): void
    {
        $center = TrainingCenter::factory()->create();
        $centerReviewer = User::factory()->create([
            'role' => Role::TesdaAdmin,
            'is_active' => true,
            'training_center_id' => $center->id,
        ]);
        $trainer = User::factory()->create([
            'role' => Role::TesdaAdmin,
            'is_active' => true,
            'training_center_id' => $center->id,
        ]);
        $program = TrainingProgram::factory()->create(['training_center_id' => $center->id]);
        TrainingAssignment::factory()->create([
            'training_program_id' => $program->id,
            'user_id' => $trainer->id,
        ]);
        $scholarship = Scholarship::factory()->create([
            'status' => 'review',
            'verified_at' => null,
            'training_center_id' => $center->id,
            'center_approval_status' => 'pending',
            'trainer_approval_status' => 'pending',
        ]);
        Event::fake([WorkspaceChanged::class]);

        $this->actingAs($centerReviewer)->post(route('scholarships.approve', $scholarship), [
            'stage' => 'center',
            'decision' => 'approved',
            'notes' => 'Center details verified.',
        ])->assertSessionHas('status', 'TESDA center approval saved. The scholarship is public after both approvals.');

        $this->assertDatabaseHas('scholarships', [
            'id' => $scholarship->id,
            'status' => 'review',
            'center_approval_status' => 'approved',
            'center_approved_by' => $centerReviewer->id,
            'trainer_approval_status' => 'pending',
        ]);

        $this->actingAs($trainer)->postJson('/api/mobile/scholarships/'.$scholarship->id.'/approval', [
            'stage' => 'trainer',
            'decision' => 'approved',
        ])->assertOk();

        $this->assertDatabaseHas('scholarships', [
            'id' => $scholarship->id,
            'status' => 'published',
            'center_approved_by' => $centerReviewer->id,
            'trainer_approval_status' => 'approved',
            'trainer_approved_by' => $trainer->id,
        ]);
        Event::assertDispatched(WorkspaceChanged::class, fn (WorkspaceChanged $event): bool => $event->resource === 'scholarships');

        $careerSeeker = User::factory()->create(['role' => Role::Youth, 'is_active' => true]);
        $this->actingAs($careerSeeker)->getJson('/api/mobile/scholarships')->assertJsonCount(1, 'scholarships.data');
    }

    public function test_only_assigned_tesda_center_staff_and_trainers_can_approve(): void
    {
        $center = TrainingCenter::factory()->create();
        $otherCenter = TrainingCenter::factory()->create();
        $scholarship = Scholarship::factory()->create([
            'status' => 'review',
            'training_center_id' => $center->id,
            'center_approval_status' => 'pending',
            'trainer_approval_status' => 'pending',
        ]);
        $wrongCenterStaff = User::factory()->create([
            'role' => Role::TesdaAdmin,
            'is_active' => true,
            'training_center_id' => $otherCenter->id,
        ]);
        $unassignedTrainer = User::factory()->create([
            'role' => Role::TesdaAdmin,
            'is_active' => true,
            'training_center_id' => $center->id,
        ]);
        $admin = User::factory()->create(['role' => Role::Admin, 'is_active' => true]);

        $this->actingAs($wrongCenterStaff)->post(route('scholarships.approve', $scholarship), [
            'stage' => 'center',
            'decision' => 'approved',
        ])->assertForbidden();

        $this->actingAs($unassignedTrainer)->post(route('scholarships.approve', $scholarship), [
            'stage' => 'trainer',
            'decision' => 'approved',
        ])->assertForbidden();

        $this->actingAs($admin)->post(route('scholarships.approve', $scholarship), [
            'stage' => 'center',
            'decision' => 'approved',
        ])->assertForbidden();

        $this->assertDatabaseHas('scholarships', [
            'id' => $scholarship->id,
            'status' => 'review',
            'center_approval_status' => 'pending',
            'trainer_approval_status' => 'pending',
        ]);
    }

    public function test_same_tesda_user_cannot_approve_both_listing_stages(): void
    {
        $center = TrainingCenter::factory()->create();
        $tesdaStaff = User::factory()->create([
            'role' => Role::TesdaAdmin,
            'is_active' => true,
            'training_center_id' => $center->id,
        ]);
        $program = TrainingProgram::factory()->create(['training_center_id' => $center->id]);
        TrainingAssignment::factory()->create([
            'training_program_id' => $program->id,
            'user_id' => $tesdaStaff->id,
        ]);
        $scholarship = Scholarship::factory()->create([
            'status' => 'review',
            'training_center_id' => $center->id,
            'center_approval_status' => 'pending',
            'trainer_approval_status' => 'pending',
        ]);

        $this->actingAs($tesdaStaff)->post(route('scholarships.approve', $scholarship), [
            'stage' => 'center',
            'decision' => 'approved',
        ])->assertRedirect();

        $this->actingAs($tesdaStaff)->post(route('scholarships.approve', $scholarship), [
            'stage' => 'trainer',
            'decision' => 'approved',
        ])->assertForbidden();

        $this->assertDatabaseHas('scholarships', [
            'id' => $scholarship->id,
            'status' => 'review',
            'center_approval_status' => 'approved',
            'trainer_approval_status' => 'pending',
        ]);
    }

    public function test_direct_publish_request_is_held_for_both_tesda_approvals(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin, 'is_active' => true]);
        $center = TrainingCenter::factory()->create();
        $program = TrainingProgram::factory()->create(['training_center_id' => $center->id]);

        $this->actingAs($admin)->postJson('/api/mobile/scholarships', [
            'title' => 'Local training grant',
            'provider' => 'Pangasinan Skills Office',
            'description' => 'Support for learners in local technical programs.',
            'source_url' => 'https://example.org/scholarship',
            'training_center_id' => $center->id,
            'training_program_id' => $program->id,
            'slot_limit' => 10,
            'status' => 'published',
        ])->assertCreated();

        $scholarship = Scholarship::query()->where('title', 'Local training grant')->firstOrFail();

        $this->assertSame('review', $scholarship->status);
        $this->assertSame('pending', $scholarship->center_approval_status);
        $this->assertSame('pending', $scholarship->trainer_approval_status);

        $careerSeeker = User::factory()->create(['role' => Role::Youth, 'is_active' => true]);
        $this->actingAs($careerSeeker)->getJson('/api/mobile/scholarships')->assertJsonCount(0, 'scholarships.data');
    }

    public function test_rejection_returns_the_listing_to_drafts_with_the_review_note(): void
    {
        $center = TrainingCenter::factory()->create();
        $centerReviewer = User::factory()->create([
            'role' => Role::TesdaAdmin,
            'is_active' => true,
            'training_center_id' => $center->id,
        ]);
        $scholarship = Scholarship::factory()->create([
            'status' => 'review',
            'training_center_id' => $center->id,
            'center_approval_status' => 'pending',
            'trainer_approval_status' => 'pending',
        ]);

        $this->actingAs($centerReviewer)->post(route('scholarships.approve', $scholarship), [
            'stage' => 'center',
            'decision' => 'rejected',
            'notes' => 'Please confirm the provider details first.',
        ])->assertSessionHas('status', 'Scholarship declined and returned to drafts.');

        $this->assertDatabaseHas('scholarships', [
            'id' => $scholarship->id,
            'status' => 'draft',
            'center_approval_status' => 'rejected',
            'center_approval_notes' => 'Please confirm the provider details first.',
        ]);
    }
}
