<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Database\Seeders\PresentationAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class PresentationAccountsSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_the_three_requested_presentation_roles(): void
    {
        config()->set('tuklas.demo_accounts', [
            'career_seeker_email' => 'demo-youth@example.test',
            'career_seeker_password' => 'Career-Seeker-Only-123!',
            'admin_email' => 'demo-admin@example.test',
            'admin_password' => 'Admin-Only-Password-123!',
            'tesda_email' => 'demo-tesda@example.test',
            'tesda_password' => 'TESDA-Only-Password-123!',
        ]);

        $this->seed(PresentationAccountsSeeder::class);

        $expectedAccounts = [
            'demo-youth@example.test' => ['role' => Role::Youth, 'password' => 'Career-Seeker-Only-123!'],
            'demo-admin@example.test' => ['role' => Role::Admin, 'password' => 'Admin-Only-Password-123!'],
            'demo-tesda@example.test' => ['role' => Role::TesdaAdmin, 'password' => 'TESDA-Only-Password-123!'],
        ];

        $this->assertDatabaseCount('users', count($expectedAccounts));

        foreach ($expectedAccounts as $email => $expected) {
            $user = User::query()->where('email', $email)->sole();
            $this->assertSame($expected['role'], $user->role);
            $this->assertTrue($user->is_active);
            $this->assertTrue($user->hasVerifiedEmail());
            $this->assertTrue(Hash::check($expected['password'], $user->password));
        }

        $this->seed(PresentationAccountsSeeder::class);
        $this->assertDatabaseCount('users', 3);
    }

    public function test_it_refuses_to_overwrite_an_existing_non_demo_user(): void
    {
        $existingUser = User::factory()->create([
            'name' => 'Existing Admin',
            'email' => 'demo-admin@example.test',
            'role' => Role::Admin,
            'password' => 'Existing-Password-123!',
        ]);
        config()->set('tuklas.demo_accounts', [
            'career_seeker_email' => 'demo-youth@example.test',
            'career_seeker_password' => 'Career-Seeker-Only-123!',
            'admin_email' => 'demo-admin@example.test',
            'admin_password' => 'Admin-Only-Password-123!',
            'tesda_email' => 'demo-tesda@example.test',
            'tesda_password' => 'TESDA-Only-Password-123!',
        ]);
        $this->expectException(RuntimeException::class);

        try {
            $this->seed(PresentationAccountsSeeder::class);
        } finally {
            $updatedUser = $existingUser->fresh();
            $this->assertSame('Existing Admin', $updatedUser->name);
            $this->assertTrue(Hash::check('Existing-Password-123!', $updatedUser->password));
            $this->assertDatabaseCount('users', 1);
        }
    }

    public function test_it_requires_a_password_before_creating_presentation_accounts(): void
    {
        config()->set('tuklas.demo_accounts', [
            'career_seeker_email' => 'demo-youth@example.test',
            'career_seeker_password' => 'Career-Seeker-Only-123!',
            'admin_email' => 'demo-admin@example.test',
            'admin_password' => 'Admin-Only-Password-123!',
            'tesda_email' => 'demo-tesda@example.test',
            'tesda_password' => null,
        ]);
        $this->expectException(RuntimeException::class);

        $this->seed(PresentationAccountsSeeder::class);
    }
}
