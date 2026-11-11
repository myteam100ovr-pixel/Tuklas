<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class AdminAccountSeeder extends Seeder
{
    public function run(): void
    {
        $config = config('tuklas.admin');

        if (blank($config['email'])) {
            throw new RuntimeException('Set TUKLAS_ADMIN_EMAIL before running the Admin account seeder.');
        }

        $admin = User::query()->where('email', $config['email'])->first()
            ?? User::query()
                ->where('email', 'demo-super_admin@tuklas.test')
                ->where('role', Role::Admin->value)
                ->first();

        if ($admin !== null) {
            $attributes = [
                'name' => $config['name'],
                'email' => $config['email'],
                'role' => Role::Admin,
                'email_verified_at' => $admin->email_verified_at ?? now(),
                'is_active' => true,
            ];

            if (filled($config['password'])) {
                $attributes['password'] = $config['password'];
            }

            $admin->forceFill($attributes)->save();

            $this->command->info('Configured Admin account is present and enabled.');

            return;
        }

        if (blank($config['password'])) {
            throw new RuntimeException('Set TUKLAS_ADMIN_PASSWORD to create the configured Admin account.');
        }

        User::forceCreate([
            'name' => $config['name'],
            'email' => $config['email'],
            'password' => $config['password'],
            'role' => Role::Admin->value,
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        $this->command->info('Configured Admin account was created.');
    }
}
