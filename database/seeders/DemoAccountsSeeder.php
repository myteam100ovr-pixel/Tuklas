<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class DemoAccountsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('Demo accounts can only be created outside production.');
        }

        $password = config('tuklas.demo_password');

        if (blank($password)) {
            throw new RuntimeException('Set TUKLAS_DEMO_PASSWORD before creating demo accounts.');
        }

        $accounts = [
            ['role' => Role::Youth, 'name' => 'Demo Career Seeker', 'email' => 'demo-youth@tuklas.test', 'date_of_birth' => '2003-01-01'],
            ['role' => Role::Trainer, 'name' => 'Demo Trainer', 'email' => 'demo-trainer@tuklas.test'],
            ['role' => Role::Admin, 'name' => 'Demo Admin', 'email' => 'admin@tuklas.test'],
        ];

        foreach ($accounts as $account) {
            $user = User::query()->firstOrNew(['email' => $account['email']]);
            $user->forceFill([
                'name' => $account['name'],
                'password' => $password,
                'role' => $account['role'],
                'email_verified_at' => $user->email_verified_at ?? now(),
                'is_active' => true,
                'date_of_birth' => $account['date_of_birth'] ?? null,
            ])->save();
        }

        $this->command->info('Three local demo accounts are ready for email and password sign-in.');
    }
}
