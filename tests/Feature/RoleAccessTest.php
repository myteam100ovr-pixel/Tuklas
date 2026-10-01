<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(Role $role, array $extra = []): User
    {
        return User::forceCreate(array_merge([
            'name' => 'Test ' . $role->value,
            'email' => $role->value . '@example.test',
            'password' => bcrypt('password-123'),
            'role' => $role->value,
            'email_verified_at' => now(),
        ], $extra));
    }

    public function test_youth_cannot_access_admin_or_trainer_areas(): void
    {
        $youth = $this->makeUser(Role::Youth);

        $this->actingAs($youth)->get('/admin')->assertForbidden();
        $this->actingAs($youth)->get('/trainer')->assertForbidden();
        $this->actingAs($youth)->get('/youth')->assertOk();
    }

    public function test_trainer_cannot_access_admin_area(): void
    {
        $trainer = $this->makeUser(Role::Trainer);

        $this->actingAs($trainer)->get('/admin')->assertForbidden();
        $this->actingAs($trainer)->get('/trainer')->assertOk();
    }

    public function test_dashboard_redirects_by_role(): void
    {
        $admin = $this->makeUser(Role::SuperAdmin);

        $this->actingAs($admin)->get('/dashboard')->assertRedirect('/admin');
    }

    public function test_inactive_user_is_blocked(): void
    {
        $youth = $this->makeUser(Role::Youth, ['is_active' => false]);

        $this->actingAs($youth)->get('/youth')->assertForbidden();
    }

    public function test_registration_ignores_posted_role(): void
    {
        $this->post('/register', [
            'name' => 'Juan Dela Cruz',
            'email' => 'juan@example.test',
            'password' => 'a-strong-passphrase-123',
            'password_confirmation' => 'a-strong-passphrase-123',
            'role' => 'super_admin',
            'terms' => true,
        ]);

        $this->assertSame(Role::Youth, User::where('email', 'juan@example.test')->firstOrFail()->role);
    }
}