<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // Akun default hanya untuk lingkungan lokal/testing. Di production buat admin secara manual.
        if (! app()->environment('local', 'testing')) {
            $this->command?->warn('AdminSeeder dilewati: bukan lingkungan local/testing.');

            return;
        }

        User::firstOrCreate(
            ['email' => 'admin@qlinic.test'],
            ['name' => 'Admin Qlinic', 'password' => 'password', 'role' => 'admin', 'is_active' => true],
        );
    }
}
