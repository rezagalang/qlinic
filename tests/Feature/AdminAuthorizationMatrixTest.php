<?php

namespace Tests\Feature;

use App\Models\Clinic;
use App\Models\QueueTicket;
use App\Models\ScheduleException;
use App\Models\Service;
use App\Models\TelemedicineSchedule;
use App\Models\User;
use Database\Seeders\ClinicSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Phase 8: setiap route panel diuji untuk tamu, petugas, dan admin. */
class AdminAuthorizationMatrixTest extends TestCase
{
    use RefreshDatabase;

    private int $serviceId;
    private int $teleId;
    private int $exceptionId;
    private int $ticketId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ClinicSeeder::class);

        $clinic = Clinic::first();
        $this->serviceId = Service::first()->id;
        $this->teleId = TelemedicineSchedule::first()->id;
        $this->exceptionId = ScheduleException::create(['clinic_id' => $clinic->id, 'date' => '2030-01-01', 'is_closed' => true])->id;
        $this->ticketId = QueueTicket::create([
            'clinic_id' => $clinic->id, 'service_id' => $this->serviceId, 'practice_date' => '2030-01-01',
            'session' => 'pagi', 'number' => 1, 'patient_name' => 'X', 'phone' => '081234567890',
            'birth_date' => '2020-01-01', 'device_token' => str_repeat('a', 40), 'status' => 'menunggu',
        ])->id;
    }

    private function user(string $role): User
    {
        return User::create([
            'name' => $role, 'email' => $role.'@qlinic.test', 'password' => 'secret123',
            'role' => $role, 'is_active' => true,
        ]);
    }

    /** @return array<int, array{0:string,1:string}> route khusus admin */
    private function adminOnly(): array
    {
        return [
            ['GET', '/admin/jadwal'],
            ['PUT', '/admin/jadwal'],
            ['POST', '/admin/jadwal/libur'],
            ['DELETE', '/admin/jadwal/libur/'.$this->exceptionId],
            ['GET', '/admin/klinik'],
            ['PUT', '/admin/klinik'],
            ['GET', '/admin/layanan'],
            ['POST', '/admin/layanan'],
            ['PUT', '/admin/layanan/'.$this->serviceId],
            ['DELETE', '/admin/layanan/'.$this->serviceId],
            ['GET', '/admin/telemedicine'],
            ['POST', '/admin/telemedicine'],
            ['DELETE', '/admin/telemedicine/'.$this->teleId],
        ];
    }

    /** @return array<int, array{0:string,1:string}> route untuk petugas dan admin */
    private function staffRoutes(): array
    {
        return [
            ['GET', '/admin/antrian'],
            ['POST', '/admin/antrian/panggil-berikutnya'],
            ['POST', '/admin/antrian/'.$this->ticketId.'/cancel'],
        ];
    }

    public function test_guests_are_redirected_to_login_on_every_panel_route(): void
    {
        foreach ([...$this->adminOnly(), ...$this->staffRoutes(), ['POST', '/admin/logout']] as [$method, $uri]) {
            $this->call($method, $uri)->assertRedirect('/admin/login');
        }
    }

    public function test_petugas_is_forbidden_on_every_admin_only_route(): void
    {
        $this->actingAs($this->user('petugas'));

        foreach ($this->adminOnly() as [$method, $uri]) {
            $this->call($method, $uri)->assertForbidden();
        }

        $this->assertNotNull(Service::find($this->serviceId));
        $this->assertNotNull(ScheduleException::find($this->exceptionId));
        $this->assertNotNull(TelemedicineSchedule::find($this->teleId));
    }

    public function test_petugas_can_use_the_queue_panel(): void
    {
        $this->actingAs($this->user('petugas'));

        $this->get('/admin/antrian')->assertOk();
        $this->post('/admin/antrian/'.$this->ticketId.'/cancel')->assertRedirect();

        $this->assertSame('dibatalkan', QueueTicket::find($this->ticketId)->status->value);
    }

    public function test_user_with_unknown_role_is_kept_out_of_the_panel(): void
    {
        $this->actingAs($this->user('pasien'))->get('/admin/antrian')->assertRedirect('/admin/login');
        $this->assertGuest();
    }

    public function test_deactivated_staff_loses_access_immediately(): void
    {
        $staff = $this->user('petugas');
        $this->actingAs($staff);
        $staff->update(['is_active' => false]);

        $this->get('/admin/antrian')->assertRedirect('/admin/login');
    }

    public function test_admin_reaches_every_admin_only_page(): void
    {
        $this->actingAs($this->user('admin'));

        foreach (['/admin/jadwal', '/admin/klinik', '/admin/layanan', '/admin/telemedicine', '/admin/antrian'] as $uri) {
            $this->get($uri)->assertOk();
        }
    }
}