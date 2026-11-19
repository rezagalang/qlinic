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

class AdminQueueTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ClinicSeeder::class);
        $this->travelTo(Carbon::parse('2026-11-18 08:00:00'));

        $this->staff = User::create([
            'name' => 'Petugas', 'email' => 'petugas@qlinic.test', 'password' => 'secret123',
            'role' => 'petugas', 'is_active' => true,
        ]);
    }

    private function ticket(int $number, string $status = 'menunggu', ?string $token = null): QueueTicket
    {
        return QueueTicket::create([
            'clinic_id' => Clinic::first()->id,
            'service_id' => Service::first()->id,
            'practice_date' => '2026-11-18',
            'session' => 'pagi',
            'number' => $number,
            'patient_name' => 'Pasien '.$number,
            'phone' => '08123456789'.$number,
            'birth_date' => '2020-01-0'.$number,
            'device_token' => $token ?? str_repeat((string) $number, 40),
            'status' => $status,
        ]);
    }

    private function scope(): array
    {
        return ['date' => '2026-11-18', 'session' => 'pagi'];
    }

    public function test_queue_panel_lists_tickets(): void
    {
        $this->ticket(1);
        $this->ticket(2);

        $this->actingAs($this->staff)
            ->get('/admin/antrian?date=2026-11-18&session=pagi')
            ->assertOk()
            ->assertSee('Pasien 1')
            ->assertSee('Pasien 2')
            ->assertSee('Panggil berikutnya');
    }

    public function test_call_next_picks_the_lowest_waiting_number(): void
    {
        $this->ticket(2);
        $this->ticket(1);

        $this->actingAs($this->staff)->post('/admin/antrian/panggil-berikutnya', $this->scope())->assertRedirect();

        $called = QueueTicket::where('number', 1)->first();
        $this->assertSame('dipanggil', $called->status->value);
        $this->assertNotNull($called->called_at);
        $this->assertSame($this->staff->id, $called->handled_by);
        $this->assertSame('menunggu', QueueTicket::where('number', 2)->first()->status->value);
    }

    public function test_call_next_is_blocked_while_another_number_is_called(): void
    {
        $this->ticket(1, 'dipanggil');
        $this->ticket(2);

        $this->actingAs($this->staff)
            ->post('/admin/antrian/panggil-berikutnya', $this->scope())
            ->assertSessionHas('error');

        $this->assertSame('menunggu', QueueTicket::where('number', 2)->first()->status->value);
    }

    public function test_call_next_with_empty_queue_shows_error(): void
    {
        $this->actingAs($this->staff)
            ->post('/admin/antrian/panggil-berikutnya', $this->scope())
            ->assertSessionHas('error');
    }

    public function test_finish_a_called_ticket(): void
    {
        $ticket = $this->ticket(1, 'dipanggil');

        $this->actingAs($this->staff)->post("/admin/antrian/{$ticket->id}/finish")->assertSessionHas('status');

        $ticket->refresh();
        $this->assertSame('selesai', $ticket->status->value);
        $this->assertNotNull($ticket->finished_at);
    }

    public function test_cannot_finish_a_waiting_ticket(): void
    {
        $ticket = $this->ticket(1);

        $this->actingAs($this->staff)->post("/admin/antrian/{$ticket->id}/finish")->assertSessionHas('error');

        $this->assertSame('menunggu', $ticket->refresh()->status->value);
    }

    public function test_skip_then_restore(): void
    {
        $ticket = $this->ticket(1, 'dipanggil');

        $this->actingAs($this->staff)->post("/admin/antrian/{$ticket->id}/skip");
        $this->assertSame('dilewati', $ticket->refresh()->status->value);

        $this->actingAs($this->staff)->post("/admin/antrian/{$ticket->id}/restore");
        $ticket->refresh();
        $this->assertSame('menunggu', $ticket->status->value);
        $this->assertNull($ticket->called_at);
    }

    public function test_cancel_a_waiting_ticket(): void
    {
        $ticket = $this->ticket(1);

        $this->actingAs($this->staff)->post("/admin/antrian/{$ticket->id}/cancel");

        $this->assertSame('dibatalkan', $ticket->refresh()->status->value);
    }

    public function test_unknown_action_is_not_routable(): void
    {
        $ticket = $this->ticket(1);

        $this->actingAs($this->staff)->post("/admin/antrian/{$ticket->id}/hapus")->assertNotFound();
    }

    public function test_guest_cannot_manage_the_queue(): void
    {
        $ticket = $this->ticket(1);

        $this->post("/admin/antrian/{$ticket->id}/call")->assertRedirect('/admin/login');
        $this->assertSame('menunggu', $ticket->refresh()->status->value);
    }

    public function test_patient_status_endpoint_reports_current_number_and_ahead_count(): void
    {
        $token = str_repeat('p', 40);
        $this->ticket(1, 'dipanggil');
        $this->ticket(2);
        $this->ticket(3, 'menunggu', $token);

        // getJson() hanya mengirim cookie jika withCredentials() dipanggil (browser mengirimnya otomatis).
        $this->withCredentials()
            ->withCookie(DeviceToken::COOKIE, $token)
            ->getJson('/antrian/status')
            ->assertOk()
            ->assertJsonPath('tickets.0.status', 'menunggu')
            ->assertJsonPath('tickets.0.current', 1)
            ->assertJsonPath('tickets.0.ahead', 1);
    }

    public function test_patient_status_endpoint_is_empty_without_cookie(): void
    {
        $this->getJson('/antrian/status')->assertOk()->assertJsonPath('tickets', []);
    }
}