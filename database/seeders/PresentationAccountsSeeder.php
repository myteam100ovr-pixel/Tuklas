<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PresentationAccountsSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            [
                'email' => config('tuklas.demo_accounts.career_seeker_email'),
                'password' => config('tuklas.demo_accounts.career_seeker_password'),
                'name' => 'Demo Career Seeker',
                'role' => Role::Youth,
                'password_env' => 'TUKLAS_DEMO_CAREER_SEEKER_PASSWORD or TUKLAS_DEMO_PASSWORD',
            ],
            [
                'email' => config('tuklas.demo_accounts.admin_email'),
                'password' => config('tuklas.demo_accounts.admin_password'),
                'name' => 'Demo PESO Admin',
                'role' => Role::Admin,
                'password_env' => 'TUKLAS_DEMO_ADMIN_PASSWORD',
            ],
            [
                'email' => config('tuklas.demo_accounts.tesda_email'),
                'password' => config('tuklas.demo_accounts.tesda_password'),
                'name' => 'Demo TESDA Admin',
                'role' => Role::TesdaAdmin,
                'password_env' => 'TUKLAS_DEMO_TESDA_PASSWORD',
            ],
        ];

        foreach ($accounts as $account) {
            if (blank($account['email'])) {
                throw new RuntimeException('Set all presentation account email variables before running this seeder.');
            }

            if (blank($account['password'])) {
                throw new RuntimeException("Set {$account['password_env']} before creating {$account['name']}.");
            }
        }

        DB::transaction(function () use ($accounts): void {
            foreach ($accounts as $account) {
                $user = User::withTrashed()->where('email', $account['email'])->first();

                if ($user?->trashed()) {
                    throw new RuntimeException("The presentation email {$account['email']} belongs to a deleted user. Choose another demo email or restore that account intentionally.");
                }

                if ($user !== null && ($user->role !== $account['role'] || ! str_starts_with($user->name, 'Demo '))) {
                    throw new RuntimeException("The presentation email {$account['email']} already belongs to a non-demo user. Choose another demo email.");
                }

                $user ??= new User;
                $user->forceFill([
                    'name' => $account['name'],
                    'email' => $account['email'],
                    'password' => $account['password'],
                    'role' => $account['role'],
                    'email_verified_at' => $user->email_verified_at ?? now(),
                    'is_active' => true,
                ])->save();
            }
        });

        $this->command?->info('Presentation Career Seeker, Admin, and TESDA accounts are ready.');
    }
}
