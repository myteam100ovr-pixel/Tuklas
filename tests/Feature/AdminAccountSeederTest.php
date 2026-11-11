<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Database\Seeders\AdminAccountSeeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class AdminAccountSeederTest extends TestCase
{
    public function test_it_creates_the_configured_admin_account(): void
    {
        config()->set('tuklas.admin', [
            'email' => 'admin@example.test',
            'name' => 'PESO Bugallon Admin',
            'password' => 'A-Strong-Local-Password-123!',
        ]);

        $this->seed(AdminAccountSeeder::class);

        $admin = User::query()->where('email', 'admin@example.test')->sole();
        $this->assertModelExists($admin);
        $this->assertSame(Role::Admin, $admin->role);
        $this->assertSame('PESO Bugallon Admin', $admin->name);
        $this->assertTrue($admin->is_active);
        $this->assertTrue($admin->hasVerifiedEmail());
        $this->assertTrue(Hash::check('A-Strong-Local-Password-123!', $admin->password));
    }

    public function test_it_converts_an_existing_account_and_keeps_its_password_when_no_new_password_is_configured(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@example.test',
            'name' => 'Old Name',
            'role' => Role::Youth,
            'password' => 'Existing-Password-123!',
            'email_verified_at' => null,
            'is_active' => false,
        ]);
        config()->set('tuklas.admin', [
            'email' => 'admin@example.test',
            'name' => 'PESO Bugallon Admin',
            'password' => null,
        ]);

        $this->seed(AdminAccountSeeder::class);

        $admin = $user->fresh();
        $this->assertSame(Role::Admin, $admin->role);
        $this->assertSame('PESO Bugallon Admin', $admin->name);
        $this->assertTrue($admin->is_active);
        $this->assertTrue($admin->hasVerifiedEmail());
        $this->assertTrue(Hash::check('Existing-Password-123!', $admin->password));
    }

    public function test_it_requires_a_password_before_creating_a_new_admin_account(): void
    {
        config()->set('tuklas.admin', [
            'email' => 'admin@example.test',
            'name' => 'PESO Bugallon Admin',
            'password' => null,
        ]);
        $this->expectException(RuntimeException::class);

        $this->seed(AdminAccountSeeder::class);
    }

    public function test_it_renames_the_old_demo_admin_to_the_configured_email(): void
    {
        $legacyAdmin = User::factory()->create([
            'email' => 'demo-super_admin@tuklas.test',
            'role' => Role::Admin,
            'password' => 'Existing-Password-123!',
        ]);
        config()->set('tuklas.admin', [
            'email' => 'admin@example.test',
            'name' => 'PESO Bugallon Admin',
            'password' => null,
        ]);

        $this->seed(AdminAccountSeeder::class);

        $admin = $legacyAdmin->fresh();
        $this->assertSame('admin@example.test', $admin->email);
        $this->assertSame(Role::Admin, $admin->role);
        $this->assertTrue(Hash::check('Existing-Password-123!', $admin->password));
    }
}
