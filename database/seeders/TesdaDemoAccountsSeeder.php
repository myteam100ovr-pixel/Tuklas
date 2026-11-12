<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TesdaDemoAccountsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (app()->isProduction() && $this->command?->option('force') !== true) {
            throw new RuntimeException('On production, run this seeder with --force to confirm these TESDA beta accounts should be public.');
        }

        $password = config('tuklas.demo_accounts.tesda_password');

        if (blank($password)) {
            throw new RuntimeException('Set TUKLAS_DEMO_TESDA_PASSWORD before creating TESDA beta accounts.');
        }

        $accounts = [];

        for ($number = 1; $number <= 50; $number++) {
            $email = sprintf('tesda-beta-%02d@tuklas.test', $number);
            $user = User::withTrashed()->where('email', $email)->first();

            if ($user?->trashed()) {
                throw new RuntimeException("The TESDA beta email {$email} belongs to a deleted user.");
            }

            if ($user !== null && ($user->role !== Role::TesdaAdmin || ! str_starts_with($user->name, 'TESDA Beta Tester '))) {
                throw new RuntimeException("The TESDA beta email {$email} already belongs to a non-demo user.");
            }

            $accounts[] = [
                'email' => $email,
                'name' => sprintf('TESDA Beta Tester %02d', $number),
                'user' => $user,
            ];
        }

        DB::transaction(function () use ($accounts, $password): void {
            foreach ($accounts as $account) {
                $user = $account['user'] ?? new User;
                $user->forceFill([
                    'name' => $account['name'],
                    'email' => $account['email'],
                    'password' => $password,
                    'role' => Role::TesdaAdmin,
                    'email_verified_at' => $user->email_verified_at ?? now(),
                    'is_active' => true,
                ])->save();
            }
        });

        $this->command?->info('Created or refreshed 50 TESDA beta accounts: tesda-beta-01@tuklas.test through tesda-beta-50@tuklas.test.');
    }
}
