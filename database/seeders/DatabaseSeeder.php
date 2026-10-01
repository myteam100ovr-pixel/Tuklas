<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $cfg = config('tuklas.superadmin');

        if (blank($cfg['email']) || blank($cfg['password'])) {
            $this->command->warn('Set TUKLAS_SUPERADMIN_EMAIL and TUKLAS_SUPERADMIN_PASSWORD in .env, then run the seeder again.');
            return;
        }

        User::forceCreate([
            'name' => $cfg['name'],
            'email' => $cfg['email'],
            'password' => Hash::make($cfg['password']),
            'role' => Role::SuperAdmin->value,
            'email_verified_at' => now(),
        ]);
    }
}