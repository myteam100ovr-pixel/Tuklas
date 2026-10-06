<?php

namespace Tests\Feature\Admin;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_super_admin_sees_users_from_every_role_including_admin_accounts(): void
    {
        $administrator = User::factory()->create([
            'name' => 'Directory Administrator',
            'email' => 'admin-directory@example.test',
        ]);
        $administrator->role = Role::SuperAdmin;
        $administrator->is_active = true;
        $administrator->email_verified_at = now();
        $administrator->save();
        $trainer = User::factory()->create([
            'name' => 'Directory Trainer',
            'email' => 'trainer-directory@example.test',
        ]);
        $trainer->role = Role::Trainer;
        $trainer->is_active = true;
        $trainer->email_verified_at = now();
        $trainer->save();
        $youth = User::factory()->create([
            'name' => 'Directory Youth',
            'email' => 'youth-directory@example.test',
        ]);
        $youth->role = Role::Youth;
        $youth->is_active = true;
        $youth->email_verified_at = now();
        $youth->save();

        $this->assertSame(Role::SuperAdmin, $administrator->role);
        $this->assertTrue($administrator->is_active);
        $this->assertTrue($administrator->hasVerifiedEmail());

        $this->actingAs($administrator)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee($administrator->name)
            ->assertSee($administrator->email)
            ->assertSee(Role::SuperAdmin->label())
            ->assertSee($trainer->email)
            ->assertSee(Role::Trainer->label())
            ->assertSee($youth->email)
            ->assertSee(Role::Youth->label())
            ->assertSee('Users')
            ->assertSee('3 total');
    }

    public function test_super_admin_can_reach_users_beyond_the_first_page(): void
    {
        $administrator = User::factory()->create([
            'name' => 'Directory Admin',
        ]);
        $administrator->role = Role::SuperAdmin;
        $administrator->is_active = true;
        $administrator->email_verified_at = now();
        $administrator->save();
        $users = collect();

        foreach (range(1, 20) as $index) {
            $users->push(User::factory()->create([
                'name' => sprintf('Directory User %02d', $index),
                'email' => sprintf('directory-user-%02d@example.test', $index),
            ]));
        }

        $this->actingAs($administrator)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('21 total')
            ->assertSee('Page 1 of 2')
            ->assertSee($administrator->email)
            ->assertDontSee($users->last()->email);

        $this->actingAs($administrator)
            ->get(route('admin.users.index', ['page' => 2]))
            ->assertOk()
            ->assertSee($users->last()->email)
            ->assertSee('Page 2 of 2');
    }

    public function test_non_admin_users_cannot_view_the_user_directory(): void
    {
        $trainer = User::factory()->create([
        ]);
        $trainer->role = Role::Trainer;
        $trainer->is_active = true;
        $trainer->email_verified_at = now();
        $trainer->save();

        $this->actingAs($trainer)
            ->get(route('admin.users.index'))
            ->assertForbidden();
    }

    public function test_guest_users_are_redirected_to_login(): void
    {
        $this->get(route('admin.users.index'))
            ->assertRedirect(route('login'));
    }
}
