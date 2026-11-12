<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Database\Seeders\TesdaDemoAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class TesdaDemoAccountsSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_fifty_active_tesda_beta_accounts_with_test_emails(): void
    {
        config()->set('tuklas.demo_accounts.tesda_password', 'TESDA-Local-Test-Password-123!');

        $this->seed(TesdaDemoAccountsSeeder::class);

        $accounts = User::query()->where('email', 'like', 'tesda-beta-%@tuklas.test')->orderBy('email')->get();

        $this->assertCount(50, $accounts);
        $this->assertSame('tesda-beta-01@tuklas.test', $accounts->first()->email);
        $this->assertSame('tesda-beta-50@tuklas.test', $accounts->last()->email);

        foreach ($accounts as $account) {
            $this->assertSame(Role::TesdaAdmin, $account->role);
            $this->assertTrue($account->is_active);
            $this->assertTrue($account->hasVerifiedEmail());
            $this->assertTrue(Hash::check('TESDA-Local-Test-Password-123!', $account->password));
        }

        $this->seed(TesdaDemoAccountsSeeder::class);
        $this->assertDatabaseCount('users', 50);
    }

    public function test_it_refuses_to_overwrite_a_non_demo_user_with_a_tesda_beta_email(): void
    {
        $existingUser = User::factory()->create([
            'name' => 'Existing Career Seeker',
            'email' => 'tesda-beta-25@tuklas.test',
            'role' => Role::Youth,
        ]);
        config()->set('tuklas.demo_accounts.tesda_password', 'TESDA-Local-Test-Password-123!');
        $this->expectException(RuntimeException::class);

        try {
            $this->seed(TesdaDemoAccountsSeeder::class);
        } finally {
            $this->assertSame('Existing Career Seeker', $existingUser->fresh()->name);
            $this->assertDatabaseCount('users', 1);
        }
    }

    public function test_it_requires_a_tesda_demo_password(): void
    {
        config()->set('tuklas.demo_accounts.tesda_password', null);
        $this->expectException(RuntimeException::class);

        $this->seed(TesdaDemoAccountsSeeder::class);
    }

    public function test_it_requires_force_before_seeding_in_production(): void
    {
        $this->app['env'] = 'production';
        config()->set('tuklas.demo_accounts.tesda_password', 'TESDA-Local-Test-Password-123!');
        $this->expectException(RuntimeException::class);

        app(TesdaDemoAccountsSeeder::class)->run();
    }
}
