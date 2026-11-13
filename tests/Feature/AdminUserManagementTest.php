<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\TrainingCenter;
use App\Models\TrainingProgram;
use App\Models\User;
use App\Notifications\WorkspaceNotice;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_admin_can_create_verified_accounts_for_every_role(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin, 'is_active' => true]);
        $center = TrainingCenter::factory()->create();
        $this->actingAs($admin);
        $this->get(route('admin.users.create'))->assertOk()->assertSee('Initial password');

        foreach (Role::cases() as $role) {
            $this->post(route('admin.users.store'), [
                'first_name' => 'New',
                'last_name' => 'Member',
                'email' => $role->value.'@example.test',
                'role' => $role->value,
                'training_center_id' => $role === Role::TesdaAdmin ? $center->id : null,
                'is_active' => 1,
                'password' => 'StrongPassword123!',
                'password_confirmation' => 'StrongPassword123!',
                'date_of_birth' => '2003-05-20',
                'contact_number' => '09123456789',
                'barangay' => 'Poblacion',
            ])->assertRedirect(route('admin.users.index'));

            $account = User::query()->where('email', $role->value.'@example.test')->sole();
            $this->assertSame($role, $account->role);
            $this->assertTrue($account->hasVerifiedEmail());
            $this->assertTrue($account->is_active);
            $this->assertSame('New Member', $account->name);
            if ($role === Role::Youth) {
                $this->assertSame('Poblacion', $account->youthProfile->barangay);
            }
        }
    }

    public function test_admin_can_search_user_directory_by_name(): void
    {
        $admin = User::factory()->create(['name' => 'PESO Admin', 'role' => Role::Admin, 'is_active' => true]);
        User::factory()->create(['name' => 'Maria Clara Santos']);
        User::factory()->create(['name' => 'Anthony Reyes']);

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['q' => 'Maria Clara']))
            ->assertOk()
            ->assertSee('Maria Clara Santos')
            ->assertDontSee('Anthony Reyes')
            ->assertSee('Enter a user’s name');

        $this->get(route('admin.users.index', ['q' => 'M']))
            ->assertOk()
            ->assertSee('Maria Clara Santos');
    }

    public function test_admin_can_edit_delete_and_restore_an_account(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin, 'is_active' => true]);
        $account = User::factory()->create(['role' => Role::Youth]);
        $this->actingAs($admin)->get(route('admin.users.edit', $account))->assertOk()->assertSee('Edit user account');

        $this->actingAs($admin)->put(route('admin.users.update', $account), [
            'first_name' => 'Edited',
            'last_name' => 'Trainer',
            'email' => 'edited@example.test',
            'role' => Role::TesdaAdmin->value,
            'is_active' => 0,
        ])->assertRedirect(route('admin.users.index'));

        $account->refresh();
        $this->assertSame('Edited Trainer', $account->name);
        $this->assertSame(Role::TesdaAdmin, $account->role);
        $this->assertFalse($account->is_active);
        $this->assertTrue($account->hasVerifiedEmail());

        $this->delete(route('admin.users.destroy', $account))->assertRedirect();
        $this->assertSoftDeleted($account);
        $this->post(route('admin.users.restore', $account->id))->assertRedirect();
        $this->assertNotSoftDeleted($account);
    }

    public function test_reset_sends_email_link_and_in_app_notice_without_exposing_a_password(): void
    {
        Notification::fake();
        $admin = User::factory()->create(['role' => Role::Admin, 'is_active' => true]);
        $account = User::factory()->create(['role' => Role::TesdaAdmin, 'is_active' => true]);

        $this->actingAs($admin)->post(route('admin.users.reset-password', $account))->assertRedirect();

        Notification::assertSentTo($account, ResetPassword::class);
        Notification::assertSentTo($account, WorkspaceNotice::class, fn (WorkspaceNotice $notice) => $notice->title === 'Password reset requested' && $notice->actionUrl === route('account.password-reset.edit'));

        $this->actingAs($account->fresh())->get(route('account.password-reset.edit'))->assertOk();
        $this->put(route('account.password-reset.update'), [
            'password' => 'NewSecurePassword123!',
            'password_confirmation' => 'NewSecurePassword123!',
        ])->assertRedirect(route('notifications.index'));
        $this->assertTrue(Hash::check('NewSecurePassword123!', $account->fresh()->password));
        $this->get(route('account.password-reset.edit'))->assertNotFound();
    }

    public function test_admin_cannot_delete_self_or_last_active_administrator(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin, 'is_active' => true]);
        $this->actingAs($admin)->delete(route('admin.users.destroy', $admin))->assertStatus(422);
        $this->assertNotSoftDeleted($admin);
    }

    public function test_admin_cannot_deactivate_their_own_account_from_the_form(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin, 'is_active' => true]);
        User::factory()->create(['role' => Role::Admin, 'is_active' => true]);

        $this->actingAs($admin)->put(route('admin.users.update', $admin), [
            'first_name' => 'Current',
            'last_name' => 'Admin',
            'email' => $admin->email,
            'role' => Role::Admin->value,
            'is_active' => '0',
        ])->assertStatus(422);

        $this->assertTrue($admin->fresh()->is_active);
    }

    public function test_non_admin_cannot_manage_users(): void
    {
        $trainer = User::factory()->create(['role' => Role::TesdaAdmin]);
        $this->actingAs($trainer)->get(route('admin.users.create'))->assertForbidden();
        $this->post(route('admin.users.store'), [])->assertForbidden();
    }

    public function test_admin_sees_different_youth_and_trainer_activity_notices(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin, 'is_active' => true]);
        $youth = User::factory()->create(['role' => Role::Youth, 'is_active' => true]);
        $trainer = User::factory()->create(['role' => Role::TesdaAdmin, 'is_active' => true]);
        $program = TrainingProgram::factory()->create();

        $this->actingAs($youth)->post(route('youth.assessment.store'), [
            'interests' => ['Agriculture'],
        ])->assertRedirect();
        $this->actingAs($trainer)->post(route('trainer.teaching.store', $program))->assertRedirect();

        $this->actingAs($admin)->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Youth activity')
            ->assertSee('TESDA activity')
            ->assertSee('updated their skills assessment')
            ->assertSee('chose to teach');
        $this->assertSame(2, $admin->unreadNotifications()->count());
    }

    public function test_account_notification_is_visible_only_to_its_owner_and_can_be_marked_read(): void
    {
        $youth = User::factory()->create(['role' => Role::Youth, 'is_active' => true]);
        $trainer = User::factory()->create(['role' => Role::TesdaAdmin, 'is_active' => true]);
        $youth->forceFill(['admin_password_reset_requested_at' => now()])->save();
        $youth->notify(new WorkspaceNotice('Password reset requested', 'Choose a new password.', 'account', null, route('account.password-reset.edit')));
        $notice = $youth->notifications()->sole();

        $this->actingAs($trainer)->post(route('notifications.read', $notice->id))->assertNotFound();
        $this->actingAs($youth)->get(route('notifications.index'))
            ->assertOk()->assertSee('Password reset requested')->assertSee('Reset my password');
        $this->post(route('notifications.read', $notice->id))->assertRedirect();
        $this->assertSame(0, $youth->unreadNotifications()->count());
    }
}
