<?php

namespace Tests\Feature;

use App\Models\ScheduleException;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\ClinicSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminScheduleTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ClinicSeeder::class);
        $this->travelTo(Carbon::parse('2026-11-18 08:00:00'));

        $this->admin = User::create([
            'name' => 'Admin', 'email' => 'admin@qlinic.test', 'password' => 'secret123',
            'role' => 'admin', 'is_active' => true,
        ]);
    }

    public function test_admin_can_update_weekly_schedule(): void
    {
        $payload = ['schedules' => [1 => ['pagi' => ['enabled' => '1', 'start_time' => '08:00', 'end_time' => '11:00', 'quota' => '10']]]];

        $this->actingAs($this->admin)->put('/admin/jadwal', $payload)->assertSessionHas('status');

        $this->assertDatabaseHas('practice_schedules', [
            'day_of_week' => 1, 'session' => 'pagi', 'start_time' => '08:00:00', 'end_time' => '11:00:00', 'quota' => 10, 'is_active' => true,
        ]);
        // sesi yang tidak dicentang dinonaktifkan, bukan dihapus
        $this->assertDatabaseHas('practice_schedules', ['day_of_week' => 1, 'session' => 'malam', 'is_active' => false]);
    }

    public function test_end_time_must_be_after_start_time(): void
    {
        $payload = ['schedules' => [1 => ['pagi' => ['enabled' => '1', 'start_time' => '11:00', 'end_time' => '08:00']]]];

        $this->actingAs($this->admin)->put('/admin/jadwal', $payload)
            ->assertSessionHasErrors('schedules.1.pagi.end_time');

        $this->assertDatabaseHas('practice_schedules', ['day_of_week' => 1, 'session' => 'pagi', 'start_time' => '09:00:00']);
    }

    public function test_admin_can_add_and_remove_a_closure(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/jadwal/libur', ['date' => '2026-11-20', 'session' => '', 'note' => 'Cuti bersama'])
            ->assertSessionHas('status');

        $exception = ScheduleException::first();
        $this->assertSame('2026-11-20', $exception->date->toDateString());
        $this->assertNull($exception->session);

        $this->actingAs($this->admin)->delete("/admin/jadwal/libur/{$exception->id}")->assertSessionHas('status');
        $this->assertSame(0, ScheduleException::count());
    }

    public function test_closure_cannot_be_in_the_past(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/jadwal/libur', ['date' => '2026-11-01'])
            ->assertSessionHasErrors('date');
    }

    public function test_closure_is_not_duplicated(): void
    {
        $this->actingAs($this->admin)->post('/admin/jadwal/libur', ['date' => '2026-11-20']);
        $this->actingAs($this->admin)->post('/admin/jadwal/libur', ['date' => '2026-11-20']);

        $this->assertSame(1, ScheduleException::count());
    }
}