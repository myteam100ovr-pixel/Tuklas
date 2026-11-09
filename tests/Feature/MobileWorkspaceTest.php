<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\TrainingProgram;
use App\Models\User;
use App\Notifications\WorkspaceNotice;
use App\Services\TesdaQualificationCatalog;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MobileWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_workspace_requires_authentication_and_verified_role(): void
    {
        $this->getJson('/api/mobile/assessment')->assertUnauthorized();
        Sanctum::actingAs(User::factory()->unverified()->create(['is_active' => true, 'role' => Role::Youth]));
        $this->getJson('/api/mobile/assessment')->assertForbidden();
    }

    public function test_youth_assessment_is_saved_and_restored_on_both_platforms(): void
    {
        $user = User::factory()->create(['is_active' => true, 'role' => Role::Youth]);
        Sanctum::actingAs($user);
        $this->postJson('/api/mobile/assessment', ['skills' => ['Communication'], 'interests' => ['Agriculture'], 'career_goal' => 'Grow food locally'])->assertOk()->assertJsonStructure(['message']);
        $this->getJson('/api/mobile/assessment')->assertOk()->assertJsonPath('profile.assessment_preferences.career_goal', 'Grow food locally');
        $this->assertDatabaseHas('youth_profiles', ['user_id' => $user->id]);
        $this->actingAs($user)->get(route('youth.assessment.index'))->assertSee('Grow food locally');
        $this->postJson('/api/mobile/assessment', ['interests' => ['Invalid']])->assertUnprocessable()->assertJsonValidationErrors('interests.0');
    }

    public function test_role_boundaries_prevent_youth_management_and_trainer_assessment(): void
    {
        Sanctum::actingAs(User::factory()->create(['is_active' => true, 'role' => Role::Youth]));
        $this->getJson('/api/mobile/admin/users')->assertForbidden();
        $this->postJson('/api/mobile/trainer/programs', ['title' => 'Course', 'status' => 'published'])->assertForbidden();
        $this->getJson('/api/mobile/tesda/choices')->assertForbidden();
        Sanctum::actingAs(User::factory()->create(['is_active' => true, 'role' => Role::Trainer]));
        $this->getJson('/api/mobile/assessment')->assertForbidden();
    }

    public function test_trainer_can_publish_and_edit_own_program_but_not_another_trainers(): void
    {
        $trainer = User::factory()->create(['is_active' => true, 'role' => Role::Trainer]);
        $other = TrainingProgram::factory()->create();
        Sanctum::actingAs($trainer);
        $this->postJson('/api/mobile/trainer/programs', ['title' => 'Mobile baking course', 'status' => 'published'])->assertOk();
        $program = TrainingProgram::where('title', 'Mobile baking course')->sole();
        $this->assertSame($trainer->id, $program->created_by);
        $this->getJson('/api/mobile/trainer/programs')->assertJsonPath('programs.data.0.title', 'Mobile baking course');
        $this->putJson('/api/mobile/trainer/programs/'.$program->id, ['title' => 'Updated course', 'status' => 'archived'])->assertOk();
        $this->assertSame('archived', $program->fresh()->status);
        $this->putJson('/api/mobile/trainer/programs/'.$other->id, ['title' => 'Not mine', 'status' => 'published'])->assertForbidden();
    }

    public function test_notifications_are_scoped_to_the_authenticated_account(): void
    {
        $user = User::factory()->create(['is_active' => true, 'role' => Role::Youth]);
        $other = User::factory()->create();
        $user->notify(new WorkspaceNotice('Your update', 'Saved on the website'));
        $other->notify(new WorkspaceNotice('Private update', 'Private'));
        Sanctum::actingAs($user);
        $this->getJson('/api/mobile/notifications')->assertJsonCount(1, 'notifications.data')->assertJsonPath('notifications.data.0.data.title', 'Your update');
        $this->postJson('/api/mobile/notifications/'.$other->notifications->first()->id.'/read')->assertNotFound();
        $this->postJson('/api/mobile/notifications/'.$user->notifications->first()->id.'/read')->assertOk();
        $this->assertNotNull($user->notifications->first()->fresh()->read_at);
        $this->assertNull($other->notifications->first()->fresh()->read_at);
        $this->postJson('/api/mobile/notifications/read-all')->assertOk();
    }

    public function test_training_plans_and_teaching_lists_share_web_records(): void
    {
        $qualification = collect(app(TesdaQualificationCatalog::class)->all()['qualifications'])->firstWhere('status', 'current');
        $user = User::factory()->create(['is_active' => true, 'role' => Role::Youth]);
        Sanctum::actingAs($user);
        $this->postJson('/api/mobile/tesda/choice', ['qualification_id' => 'invalid'])->assertUnprocessable();
        $this->postJson('/api/mobile/tesda/choice', ['qualification_id' => $qualification['id'], 'user_id' => $user->id])->assertForbidden();
        $this->postJson('/api/mobile/tesda/choice', ['qualification_id' => $qualification['id']])->assertOk();
        $this->getJson('/api/mobile/tesda/workspace')->assertOk()->assertJsonPath('choiceUser.tesda_choice.qualification_id', $qualification['id']);
        $trainer = User::factory()->create(['is_active' => true, 'role' => Role::Trainer]);
        Sanctum::actingAs($trainer);
        $this->getJson('/api/mobile/tesda/choices')->assertOk()->assertJsonPath('youthUsers.data.0.id', $user->id);
        $this->postJson('/api/mobile/tesda/choice', ['qualification_id' => $qualification['id'], 'user_id' => $user->id])->assertOk();
        $this->assertSame($trainer->id, $user->tesdaChoice()->sole()->selected_by);
        $this->postJson('/api/mobile/trainer/qualifications', ['qualification_id' => 'invalid'])->assertUnprocessable();
        $this->postJson('/api/mobile/trainer/qualifications', ['qualification_id' => $qualification['id']])->assertOk();
        $this->assertSame(1, $trainer->trainerQualifications()->count());
        $this->deleteJson('/api/mobile/trainer/qualifications/'.$qualification['id'])->assertOk();
        $this->assertSame(0, $trainer->trainerQualifications()->count());
        $program = TrainingProgram::factory()->create(['status' => 'published']);
        $this->postJson('/api/mobile/trainer/teaching/'.$program->id)->assertOk();
        $this->assertSame(1, $trainer->teachingAssignments()->count());
        $this->deleteJson('/api/mobile/trainer/teaching/'.$program->id)->assertOk();
        $this->assertSame(0, $trainer->teachingAssignments()->count());
    }

    public function test_administrator_can_manage_users_without_exposing_passwords(): void
    {
        $admin = User::factory()->create(['is_active' => true, 'role' => Role::SuperAdmin]);
        Sanctum::actingAs($admin);
        $data = ['first_name' => 'Mobile', 'last_name' => 'Trainer', 'email' => 'trainer@example.test', 'role' => 'trainer', 'is_active' => true];
        $this->postJson('/api/mobile/admin/users', [...$data, 'password' => 'Password123!', 'password_confirmation' => 'Password123!'])->assertOk();
        $user = User::where('email', $data['email'])->sole();
        $this->getJson('/api/mobile/admin/users/'.$user->id)->assertOk()->assertJsonMissingPath('account.password')->assertJsonPath('account.first_name', 'Mobile');
        $this->putJson('/api/mobile/admin/users/'.$user->id, [...$data, 'first_name' => 'Updated'])->assertOk();
        $this->assertSame('Updated Trainer', $user->fresh()->name);
        $this->deleteJson('/api/mobile/admin/users/'.$admin->id)->assertUnprocessable();
        $this->deleteJson('/api/mobile/admin/users/'.$user->id)->assertOk();
        $this->assertSoftDeleted($user);
        $this->postJson('/api/mobile/admin/users/'.$user->id.'/restore')->assertOk();
        $this->assertNotSoftDeleted($user);
    }

    public function test_mobile_registration_stores_the_same_youth_details_as_web(): void
    {
        Event::fake([Registered::class]);
        $this->postJson('/api/mobile/auth/register', [
            'first_name' => 'New', 'last_name' => 'Youth', 'date_of_birth' => now()->subYears(20)->toDateString(),
            'contact_number' => '09123456789', 'barangay' => 'Poblacion', 'email' => 'new@example.test',
            'password' => 'Password123!', 'password_confirmation' => 'Password123!', 'device_name' => 'test', 'terms' => true,
        ])->assertCreated()->assertJsonPath('user.name', 'New Youth');
        $this->assertDatabaseHas('youth_profiles', ['contact_number' => '09123456789', 'barangay' => 'Poblacion']);
        Event::assertDispatched(Registered::class);
    }
}
