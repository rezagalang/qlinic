<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database (idempotent).
     */
    public function run(): void
    {
        $this->call([
            ClinicSeeder::class,
            AdminSeeder::class,
        ]);
    }
}