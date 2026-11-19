<?php

namespace Tests\Feature;

use App\Models\Clinic;
use App\Models\PracticeSchedule;
use App\Models\QueueTicket;
use App\Models\ScheduleException;
use App\Models\Service;
use App\Services\RegistrationWindow;
use Carbon\Carbon;
use Database\Seeders\ClinicSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationWindowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ClinicSeeder::class);
    }

    private function statuses(): array
    {
        $days = app(RegistrationWindow::class)->days(Clinic::first());

        return collect($days[0]['sessions'])->pluck('status', 'key')->all();
    }

    public function test_morning_session_is_not_open_before_the_window(): void
    {
        // Rabu 2026-11-18; sesi Pagi mulai 09:00 -> dibuka 06:00
        $this->travelTo(Carbon::parse('2026-11-18 05:00:00'));

        $this->assertSame(['pagi' => 'not_yet', 'sore' => 'off', 'malam' => 'not_yet'], $this->statuses());
    }

    public function test_morning_session_is_open_inside_the_window(): void
    {
        $this->travelTo(Carbon::parse('2026-11-18 08:00:00'));

        $this->assertSame('open', $this->statuses()['pagi']);
    }

    public function test_morning_session_is_closed_after_practice_ends(): void
    {
        $this->travelTo(Carbon::parse('2026-11-18 13:00:00'));

        $this->assertSame('closed', $this->statuses()['pagi']);
        $this->assertSame('not_yet', $this->statuses()['malam']);
    }

    public function test_sunday_has_no_practice(): void
    {
        $this->travelTo(Carbon::parse('2026-11-22 08:00:00')); // Minggu

        $this->assertSame(['pagi' => 'off', 'sore' => 'off', 'malam' => 'off'], $this->statuses());
    }

    public function test_schedule_exception_closes_the_day(): void
    {
        $this->travelTo(Carbon::parse('2026-11-18 08:00:00'));
        ScheduleException::create(['clinic_id' => Clinic::first()->id, 'date' => '2026-11-18', 'session' => null, 'is_closed' => true]);

        $this->assertSame(['pagi' => 'off', 'sore' => 'off', 'malam' => 'off'], $this->statuses());
    }

    public function test_full_quota_is_reported(): void
    {
        $this->travelTo(Carbon::parse('2026-11-18 08:00:00'));
        $clinic = Clinic::first();
        PracticeSchedule::where('day_of_week', 3)->where('session', 'pagi')->update(['quota' => 1]);

        QueueTicket::create([
            'clinic_id' => $clinic->id, 'service_id' => Service::first()->id, 'practice_date' => '2026-11-18',
            'session' => 'pagi', 'number' => 1, 'patient_name' => 'A', 'phone' => '081234567890',
            'birth_date' => '2020-01-01', 'device_token' => str_repeat('a', 40), 'status' => 'menunggu',
        ]);

        $this->assertSame('full', $this->statuses()['pagi']);
    }

    public function test_time_label_matches_reference_format(): void
    {
        $this->travelTo(Carbon::parse('2026-11-18 08:00:00'));
        $days = app(RegistrationWindow::class)->days(Clinic::first());

        $this->assertSame('9.00 s/d 12.30', $days[0]['sessions'][0]['time']);
        $this->assertSame('19.00 s/d 21.30', $days[0]['sessions'][2]['time']);
        $this->assertCount(4, $days);
    }
}
