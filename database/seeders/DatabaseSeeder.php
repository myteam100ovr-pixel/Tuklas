<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $cfg = config('tuklas.superadmin');

        if (blank($cfg['email'])) {
            throw new RuntimeException('Set TUKLAS_SUPERADMIN_EMAIL before running the database seeder.');
        }

        $admin = User::query()->where('email', $cfg['email'])->first();

        if ($admin !== null) {
            $admin->forceFill([
                'role' => Role::SuperAdmin,
                'email_verified_at' => $admin->email_verified_at ?? now(),
                'is_active' => true,
            ])->save();

            $this->command->info('Configured super admin account is present and enabled.');
        } else {
            if (blank($cfg['password'])) {
                throw new RuntimeException('Set TUKLAS_SUPERADMIN_PASSWORD to create the configured super admin account.');
            }

            User::forceCreate([
                'name' => $cfg['name'],
                'email' => $cfg['email'],
                'password' => $cfg['password'],
                'role' => Role::SuperAdmin->value,
                'email_verified_at' => now(),
                'is_active' => true,
            ]);

            $this->command->info('Configured super admin account was created.');
        }

        $this->call([
            TesdaRegisteredTrainingProgramSeeder::class,
            PangasinanTrainingCenterSeeder::class,
            PhilJobNetPangasinanSeeder::class,
            TesdaScholarshipInfoSeeder::class,
        ]);
    }
}
