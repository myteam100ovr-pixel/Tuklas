<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\TrainingProgram;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(Role $role, array $extra = []): User
    {
        return User::forceCreate(array_merge([
            'name' => 'Test '.$role->value,
            'email' => $role->value.'@example.test',
            'password' => bcrypt('password-123'),
            'role' => $role->value,
            'is_active' => true,
            'email_verified_at' => now(),
        ], $extra));
    }

    public function test_each_role_sees_its_own_dashboard(): void
    {
        $this->actingAs($this->makeUser(Role::SuperAdmin))->get('/admin')
            ->assertOk()->assertSee('Setup checklist')->assertSee('Latest youth accounts');

        $this->actingAs($this->makeUser(Role::Trainer, ['email' => 't@example.test']))->get('/trainer')
            ->assertOk()->assertSee('Programs by status')->assertSee('No programs yet');

        $this->actingAs($this->makeUser(Role::Youth, ['email' => 'y@example.test']))->get('/youth')
            ->assertOk()->assertSee('Profile strength')->assertSee('Next steps');
    }

    public function test_trainer_only_sees_programs_they_created(): void
    {
        $me = $this->makeUser(Role::Trainer);
        $other = $this->makeUser(Role::Trainer, ['email' => 'other@example.test']);

        TrainingProgram::create(['title' => 'My Own Program', 'status' => 'published', 'created_by' => $me->id]);
        TrainingProgram::create(['title' => 'Someone Elses Program', 'status' => 'published', 'created_by' => $other->id]);

        $this->actingAs($me)->get('/trainer')
            ->assertSee('My Own Program')
            ->assertDontSee('Someone Elses Program');
    }

    public function test_youth_details_are_saved(): void
    {
        $youth = $this->makeUser(Role::Youth, ['is_active' => true]);

        $this->actingAs($youth)->put('/youth/profile', [
            'date_of_birth' => now()->subYears(21)->toDateString(),
            'barangay' => 'Sample Barangay',
            'educational_attainment' => 'Senior high school graduate',
        ])->assertRedirect(route('profile.show'));

        $this->assertSame('Sample Barangay', $youth->fresh()->youthProfile->barangay);
    }

    public function test_youth_overview_shows_profile_skills_credentials_and_latest_scan_recommendations(): void
    {
        $youth = $this->makeUser(Role::Youth, ['is_active' => true]);
        $youth->youthProfile()->create([
            'skills' => ['PHP', 'Communication'],
            'credentials' => ['National Certificate II in Cookery'],
        ]);
        $youth->documentScans()->create([
            'doc_type' => 'document',
            'original_name' => 'resume.pdf',
            'mime' => 'application/pdf',
            'size' => 1024,
            'status' => 'done',
            'progress' => 100,
            'result' => [
                'summary' => 'Private scan summary',
                'job_roles' => ['Junior developer'],
                'tesda_training' => ['Computer Systems Servicing NC II'],
            ],
        ]);
        $otherYouth = $this->makeUser(Role::Youth, ['email' => 'other-youth@example.test']);
        $otherYouth->documentScans()->create([
            'doc_type' => 'document',
            'original_name' => 'other.pdf',
            'mime' => 'application/pdf',
            'size' => 1024,
            'status' => 'done',
            'progress' => 100,
            'result' => ['job_roles' => ['Private other user recommendation']],
        ]);

        $this->actingAs($youth)->get('/youth')
            ->assertOk()
            ->assertSee('Your career insights')
            ->assertSee('Communication')
            ->assertSee('National Certificate II in Cookery')
            ->assertSee('Junior developer')
            ->assertSee('Computer Systems Servicing NC II')
            ->assertDontSee('Private other user recommendation');
    }

    public function test_guardian_is_required_under_18(): void
    {
        $youth = $this->makeUser(Role::Youth);

        $this->actingAs($youth)->put('/youth/profile', [
            'date_of_birth' => now()->subYears(16)->toDateString(),
        ])->assertSessionHasErrors(['guardian_name', 'guardian_relationship', 'guardian_contact']);

        $this->actingAs($youth)->put('/youth/profile', [
            'date_of_birth' => now()->subYears(16)->toDateString(),
            'guardian_name' => 'Maria Santos',
            'guardian_relationship' => 'Mother',
            'guardian_contact' => '0900 000 0000',
        ])->assertSessionHasNoErrors();
    }

    public function test_age_must_be_between_15_and_30(): void
    {
        $youth = $this->makeUser(Role::Youth);

        foreach ([12, 45] as $age) {
            $this->actingAs($youth)->put('/youth/profile', [
                'date_of_birth' => now()->subYears($age)->toDateString(),
                'guardian_name' => 'X', 'guardian_relationship' => 'Y', 'guardian_contact' => '1',
            ])->assertSessionHasErrors('date_of_birth');
        }
    }

    public function test_only_youth_can_update_youth_details(): void
    {
        $this->actingAs($this->makeUser(Role::Trainer))
            ->put('/youth/profile', ['date_of_birth' => now()->subYears(20)->toDateString()])
            ->assertForbidden();
    }
}
