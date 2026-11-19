<?php

namespace Tests\Feature;

use App\Models\Clinic;
use App\Models\QueueTicket;
use App\Models\Service;
use App\Models\User;
use App\Support\DeviceToken;
use Carbon\Carbon;
use Database\Seeders\ClinicSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Phase 8: pasien hanya melihat antrian perangkatnya sendiri; keluaran di-escape. */
class PatientPrivacyTest extends TestCase
{
    use RefreshDatabase;

    private const MINE = 'm';
    private const OTHER = 'o';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ClinicSeeder::class);
        $this->travelTo(Carbon::parse('2026-11-18 08:00:00'));
    }

    private function ticket(int $number, string $token, array $override = []): QueueTicket
    {
        return QueueTicket::create(array_merge([
            'clinic_id' => Clinic::first()->id,
            'service_id' => Service::first()->id,
            'practice_date' => '2026-11-18',
            'session' => 'pagi',
            'number' => $number,
            'patient_name' => 'Pasien '.$number,
            'phone' => '08123456780'.$number,
            'birth_date' => '2020-01-0'.$number,
            'device_token' => str_repeat($token, 40),
            'status' => 'menunggu',
        ], $override));
    }

    private function device(string $token): static
    {
        // getJson() hanya mengirim cookie jika withCredentials() dipanggil.
        return $this->withCredentials()->withCookie(DeviceToken::COOKIE, str_repeat($token, 40));
    }

    public function test_another_device_cannot_see_my_ticket(): void
    {
        $this->ticket(1, self::MINE, ['patient_name' => 'Rahasia Saya']);

        $this->device(self::OTHER)->get('/antrian')->assertOk()->assertDontSee('Rahasia Saya');
        $this->device(self::OTHER)->getJson('/antrian/status')->assertOk()->assertJsonCount(0, 'tickets');
        $this->getJson('/antrian/status')->assertOk()->assertJsonCount(0, 'tickets');
    }

    public function test_malformed_device_cookie_is_ignored(): void
    {
        $this->ticket(1, self::MINE);

        $this->withCredentials()->withCookie(DeviceToken::COOKIE, 'pendek')->getJson('/antrian/status')->assertJsonCount(0, 'tickets');
    }

    public function test_status_endpoint_only_returns_own_tickets(): void
    {
        $mine = $this->ticket(1, self::MINE);
        $this->ticket(2, self::OTHER);

        $this->device(self::MINE)->getJson('/antrian/status')
            ->assertJsonCount(1, 'tickets')
            ->assertJsonPath('tickets.0.id', $mine->id);
    }

    public function test_cancelled_and_past_tickets_are_hidden_from_my_queue(): void
    {
        $this->ticket(1, self::MINE, ['patient_name' => 'Sudah Batal', 'status' => 'dibatalkan']);
        $this->ticket(2, self::MINE, ['patient_name' => 'Kemarin', 'practice_date' => '2026-11-17']);

        $this->device(self::MINE)->get('/antrian')
            ->assertDontSee('Sudah Batal')
            ->assertDontSee('Kemarin');
    }

    public function test_recovery_skips_cancelled_and_past_tickets(): void
    {
        $this->ticket(1, self::OTHER, ['status' => 'dibatalkan']);
        $this->ticket(2, self::OTHER, ['practice_date' => '2026-11-17', 'phone' => '081234567801', 'birth_date' => '2020-01-01']);

        $this->post('/antrian/pulihkan', ['phone' => '081234567801', 'birth_date' => '2020-01-01'])
            ->assertSessionHasErrors('phone', null, 'recover');

        $this->assertSame(0, QueueTicket::where('device_token', '!=', str_repeat(self::OTHER, 40))->count());
    }

    public function test_patient_input_is_escaped_on_patient_and_admin_pages(): void
    {
        $xss = '<script>alert(1)</script>';
        $this->ticket(1, self::MINE, ['patient_name' => $xss]);

        $this->device(self::MINE)->get('/antrian')
            ->assertOk()
            ->assertDontSee($xss, false)
            ->assertSee($xss);

        $admin = User::create([
            'name' => 'Admin', 'email' => 'admin@qlinic.test', 'password' => 'secret123',
            'role' => 'admin', 'is_active' => true,
        ]);

        $this->actingAs($admin)->get('/admin/antrian?date=2026-11-18&session=pagi')
            ->assertOk()
            ->assertDontSee($xss, false)
            ->assertSee($xss);
    }
}