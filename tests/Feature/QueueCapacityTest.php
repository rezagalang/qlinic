<?php

namespace Tests\Feature;

use App\Enums\PracticeSession;
use App\Models\Clinic;
use App\Models\PracticeSchedule;
use App\Models\QueueTicket;
use App\Models\Service;
use App\Services\RegistrationWindow;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Database\Seeders\ClinicSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Phase 8: kuota, penomoran, batas jam pendaftaran, dan validasi tanggal/sesi di backend. */
class QueueCapacityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ClinicSeeder::class);
        $this->travelTo(Carbon::parse('2026-11-18 08:00:00')); // Rabu, sesi Pagi terbuka
    }

    private function payload(array $override = []): array
    {
        return array_merge([
            'date' => '2026-11-18',
            'session' => 'pagi',
            'service_id' => Service::first()->id,
            'patient_name' => 'Budi',
            'phone' => '081234567890',
            'birth_date' => '2020-05-01',
            'gender' => 'L',
            'address' => 'Jl. Mawar No. 1',
            'complaint' => null,
        ], $override);
    }

    private function other(int $i): array
    {
        return $this->payload(['patient_name' => 'Pasien '.$i, 'phone' => '08123456780'.$i]);
    }

    private function windowStatus(string $time, string $session = 'pagi'): string
    {
        return app(RegistrationWindow::class)->check(
            Clinic::first(),
            '2026-11-18',
            PracticeSession::from($session),
            CarbonImmutable::parse('2026-11-18 '.$time),
        )['status'];
    }

    public function test_application_uses_jakarta_timezone(): void
    {
        $this->assertSame('Asia/Jakarta', config('app.timezone'));
    }

    public function test_registration_is_rejected_by_the_server_when_quota_is_full(): void
    {
        PracticeSchedule::where('day_of_week', 3)->where('session', 'pagi')->update(['quota' => 2]);

        $this->post('/antrian', $this->other(1))->assertRedirect('/antrian');
        $this->post('/antrian', $this->other(2))->assertRedirect('/antrian');
        $this->post('/antrian', $this->other(3))->assertSessionHasErrors('session');

        $this->assertSame(2, QueueTicket::count());
    }

    public function test_cancelled_ticket_frees_quota_and_its_number_is_not_reused(): void
    {
        PracticeSchedule::where('day_of_week', 3)->where('session', 'pagi')->update(['quota' => 1]);

        $this->post('/antrian', $this->other(1));
        QueueTicket::first()->update(['status' => 'dibatalkan']);

        $this->post('/antrian', $this->other(2))->assertRedirect('/antrian');

        $this->assertSame([1, 2], QueueTicket::orderBy('number')->pluck('number')->all());
    }

    public function test_same_person_can_register_again_after_cancelling(): void
    {
        $this->post('/antrian', $this->payload());
        QueueTicket::first()->update(['status' => 'dibatalkan']);

        $this->post('/antrian', $this->payload())->assertSessionHasNoErrors()->assertRedirect('/antrian');

        $this->assertSame(2, QueueTicket::count());
    }

    public function test_database_rejects_a_duplicate_number_as_a_second_safety_net(): void
    {
        $make = fn () => QueueTicket::create([
            'clinic_id' => Clinic::first()->id, 'service_id' => Service::first()->id,
            'practice_date' => '2026-11-18', 'session' => 'pagi', 'number' => 1,
            'patient_name' => 'X', 'phone' => '081234567890', 'birth_date' => '2020-01-01',
            'device_token' => str_repeat('z', 40), 'status' => 'menunggu',
        ]);

        $make();

        $this->expectException(QueryException::class);
        $make();
    }

    public function test_window_boundaries_are_exact(): void
    {
        // Pagi 09:00-12:30, dibuka 3 jam sebelumnya = 06:00.
        $this->assertSame('not_yet', $this->windowStatus('05:59:59'));
        $this->assertSame('open', $this->windowStatus('06:00:00'));
        $this->assertSame('open', $this->windowStatus('12:29:59'));
        $this->assertSame('closed', $this->windowStatus('12:30:00'));
    }

    public function test_opening_hours_follow_the_clinic_setting(): void
    {
        Clinic::first()->update(['registration_hours_before' => 1]);

        $this->assertSame('not_yet', $this->windowStatus('07:59:59'));
        $this->assertSame('open', $this->windowStatus('08:00:00'));
    }

    public function test_past_dates_and_dates_beyond_the_booking_range_are_rejected(): void
    {
        $this->post('/antrian', $this->payload(['date' => '2026-11-17']))->assertSessionHasErrors('session');
        // booking_days_ahead = 4 -> tanggal terakhir 21 Nov; Senin 23 Nov sudah di luar jangkauan.
        $this->post('/antrian', $this->payload(['date' => '2026-11-23']))->assertSessionHasErrors('session');
        // Hari terakhir yang sah tetap menunggu jam pembukaannya.
        $this->post('/antrian', $this->payload(['date' => '2026-11-21']))->assertSessionHasErrors('session');

        $this->assertSame(0, QueueTicket::count());
    }

    public function test_unknown_session_and_inactive_service_are_rejected(): void
    {
        $this->post('/antrian', $this->payload(['session' => 'siang']))->assertSessionHasErrors('session');

        Service::first()->update(['is_active' => false]);
        $this->post('/antrian', $this->payload())->assertSessionHasErrors('service_id');

        $this->assertSame(0, QueueTicket::count());
    }

    public function test_registering_again_after_being_skipped_cancels_the_old_ticket(): void
    {
        $this->post('/antrian', $this->payload());
        QueueTicket::first()->update(['status' => 'dilewati']);

        $this->post('/antrian', $this->payload())->assertSessionHasNoErrors()->assertRedirect('/antrian');

        $this->assertSame('dibatalkan', QueueTicket::where('number', 1)->first()->status->value);
        $this->assertSame('menunggu', QueueTicket::where('number', 2)->first()->status->value);
        $this->assertSame(1, QueueTicket::where('status', 'menunggu')->count());
    }

    public function test_skipped_ticket_of_another_person_is_left_alone(): void
    {
        $this->post('/antrian', $this->other(1));
        QueueTicket::first()->update(['status' => 'dilewati']);

        $this->post('/antrian', $this->other(2))->assertRedirect('/antrian');

        $this->assertSame('dilewati', QueueTicket::where('number', 1)->first()->status->value);
    }
}