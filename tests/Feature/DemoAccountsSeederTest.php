<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Database\Seeders\DemoAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DemoAccountsSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_local_demo_seeder_uses_the_canonical_accounts_and_shared_password(): void
    {
        $password = 'Local-Shared-Demo-Password-123!';
        config()->set('tuklas.demo_password', $password);
        $this->seed(DemoAccountsSeeder::class);

        $accounts = [
            'demo-youth@tuklas.test' => Role::Youth,
            'demo-admin@tuklas.test' => Role::Admin,
            'demo-tesda@tuklas.test' => Role::TesdaAdmin,
        ];

        foreach ($accounts as $email => $role) {
            $user = User::query()->where('email', $email)->sole();

            $this->assertSame($role, $user->role);
            $this->assertTrue($user->is_active);
            $this->assertTrue($user->hasVerifiedEmail());
            $this->assertTrue(Hash::check($password, $user->password));
        }

        $this->assertDatabaseMissing('users', ['email' => 'admin@tuklas.test']);
    }
}
