<?php

namespace Tests\Feature;

use App\Models\TelemedicineSchedule;
use App\Models\User;
use Database\Seeders\ClinicSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTelemedicineTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ClinicSeeder::class);
        $this->admin = User::create(['name' => 'Admin', 'email' => 'a@qlinic.test', 'password' => 'secret123', 'role' => 'admin', 'is_active' => true]);
    }

    public function test_admin_can_add_a_schedule(): void
    {
        $this->actingAs($this->admin)->get('/admin/telemedicine')->assertOk()->assertSee('Jadwal Telemedicine');

        $this->actingAs($this->admin)
            ->post('/admin/telemedicine', ['day_of_week' => 6, 'start_time' => '10:00', 'end_time' => '12:00', 'note' => 'Sabtu pagi'])
            ->assertSessionHas('status');

        $this->assertDatabaseHas('telemedicine_schedules', ['day_of_week' => 6, 'start_time' => '10:00:00', 'end_time' => '12:00:00', 'note' => 'Sabtu pagi', 'is_active' => true]);
    }

    public function test_end_must_be_after_start_and_day_must_be_valid(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/telemedicine', ['day_of_week' => 9, 'start_time' => '12:00', 'end_time' => '10:00'])
            ->assertSessionHasErrors(['day_of_week', 'end_time']);
    }

    public function test_admin_can_delete_a_schedule(): void
    {
        $schedule = TelemedicineSchedule::first();

        $this->actingAs($this->admin)->delete("/admin/telemedicine/{$schedule->id}")->assertSessionHas('status');

        $this->assertDatabaseMissing('telemedicine_schedules', ['id' => $schedule->id]);
    }

    public function test_petugas_is_forbidden(): void
    {
        $petugas = User::create(['name' => 'P', 'email' => 'p@qlinic.test', 'password' => 'secret123', 'role' => 'petugas', 'is_active' => true]);

        $this->actingAs($petugas)->get('/admin/telemedicine')->assertForbidden();
    }
}