<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(AdminAccountSeeder::class);

        $this->call([
            TesdaRegisteredTrainingProgramSeeder::class,
            PangasinanTrainingCenterSeeder::class,
            PhilJobNetPangasinanSeeder::class,
            TesdaScholarshipInfoSeeder::class,
        ]);
    }
}
