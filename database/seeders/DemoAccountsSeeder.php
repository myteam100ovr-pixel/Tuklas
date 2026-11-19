<?php

namespace Database\Seeders;

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

        if (blank(config('tuklas.demo_password'))) {
            throw new RuntimeException('Set TUKLAS_DEMO_PASSWORD before creating demo accounts.');
        }

        $sharedLocalPassword = config('tuklas.demo_password');
        config()->set('tuklas.demo_accounts.career_seeker_password', $sharedLocalPassword);
        config()->set('tuklas.demo_accounts.admin_password', $sharedLocalPassword);
        config()->set('tuklas.demo_accounts.tesda_password', $sharedLocalPassword);

        $this->call(PresentationAccountsSeeder::class);

        $this->command?->info('Three local demo accounts are ready for email and password sign-in.');
    }
}
