<?php

namespace Tests\Feature;

use Database\Seeders\ClinicSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Phase 8: pembatasan laju pada endpoint yang bisa disalahgunakan. */
class RateLimitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ClinicSeeder::class);
    }

    public function test_registration_is_limited_to_ten_attempts_per_minute(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->post('/antrian', [])->assertStatus(302);
        }

        $this->post('/antrian', [])->assertStatus(429);
    }

    public function test_recovery_is_limited_to_five_attempts_per_minute(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/antrian/pulihkan', ['phone' => '081234567890', 'birth_date' => '2020-01-01'])->assertStatus(302);
        }

        $this->post('/antrian/pulihkan', ['phone' => '081234567890', 'birth_date' => '2020-01-01'])->assertStatus(429);
    }

    public function test_admin_login_is_limited_to_five_attempts_per_minute(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/admin/login', ['email' => 'x@qlinic.test', 'password' => 'salah'])->assertStatus(302);
        }

        $this->post('/admin/login', ['email' => 'x@qlinic.test', 'password' => 'salah'])->assertStatus(429);
    }
}