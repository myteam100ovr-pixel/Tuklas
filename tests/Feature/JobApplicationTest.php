<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Employer;
use App\Models\JobApplication;
use App\Models\JobOpportunity;
use App\Models\User;
use App\Notifications\WorkspaceNotice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class JobApplicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_career_seeker_can_apply_to_a_published_job_and_peso_is_notified(): void
    {
        Notification::fake();
        $careerSeeker = User::factory()->create(['role' => Role::Youth, 'is_active' => true]);
        $admin = User::factory()->create(['role' => Role::Admin, 'is_active' => true]);
        $job = JobOpportunity::factory()->for(Employer::factory()->state(['is_verified' => true]))->create([
            'status' => 'published',
            'requirements' => 'At least one year of customer service experience.',
        ]);

        $this->actingAs($careerSeeker)->post(route('job-applications.store', $job), [
            'qualification_summary' => 'I have two years of customer service experience and relevant training.',
            'application_message' => 'I am available for an interview this week.',
        ])->assertRedirect();

        $application = JobApplication::query()->sole();
        $this->assertSame($job->id, $application->job_opportunity_id);
        $this->assertSame($careerSeeker->id, $application->user_id);
        $this->assertSame('submitted', $application->status);
        $this->assertSame('I have two years of customer service experience and relevant training.', $application->qualification_summary);
        Notification::assertSentTo($admin, WorkspaceNotice::class, fn (WorkspaceNotice $notice): bool => $notice->title === 'New job application');
    }

    public function test_career_seeker_cannot_submit_a_duplicate_job_application(): void
    {
        $careerSeeker = User::factory()->create(['role' => Role::Youth, 'is_active' => true]);
        $job = JobOpportunity::factory()->create(['status' => 'published']);
        JobApplication::factory()->for($job, 'job')->for($careerSeeker, 'user')->create();

        $this->actingAs($careerSeeker)->post(route('job-applications.store', $job), [
            'qualification_summary' => 'I have two years of customer service experience and relevant training.',
        ])->assertUnprocessable();

        $this->assertDatabaseCount('job_applications', 1);
    }

    public function test_peso_can_review_an_application_and_must_explain_a_non_selection(): void
    {
        Notification::fake();
        $admin = User::factory()->create(['role' => Role::Admin, 'is_active' => true]);
        $careerSeeker = User::factory()->create(['role' => Role::Youth, 'is_active' => true]);
        $application = JobApplication::factory()->for($careerSeeker, 'user')->create();

        $this->actingAs($admin)->get(route('admin.job-applications.index'))
            ->assertOk()
            ->assertSee($application->job->title)
            ->assertSee($careerSeeker->email);

        $this->put(route('admin.job-applications.update', $application), [
            'status' => 'not_selected',
        ])->assertSessionHasErrors('decision_reason');

        $this->put(route('admin.job-applications.update', $application), [
            'status' => 'not_selected',
            'decision_reason' => 'The required certificate was not included with the application.',
        ])->assertRedirect();

        $this->assertDatabaseHas('job_applications', [
            'id' => $application->id,
            'status' => 'not_selected',
            'decision_reason' => 'The required certificate was not included with the application.',
            'reviewed_by' => $admin->id,
        ]);
        Notification::assertSentTo($careerSeeker, WorkspaceNotice::class, fn (WorkspaceNotice $notice): bool => $notice->title === 'Job application update');
    }

    public function test_tesda_cannot_access_peso_job_applications(): void
    {
        $tesda = User::factory()->create(['role' => Role::TesdaAdmin, 'is_active' => true]);
        $application = JobApplication::factory()->create();

        $this->actingAs($tesda)->get(route('admin.job-applications.index'))->assertForbidden();
        $this->put(route('admin.job-applications.update', $application), [
            'status' => 'hired',
        ])->assertForbidden();
    }
}
