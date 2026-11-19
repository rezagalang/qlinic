<?php

namespace Tests\Feature;

use App\Models\Clinic;
use App\Models\QueueTicket;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\ClinicSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ClinicSeeder::class);
        $this->admin = User::create(['name' => 'Admin', 'email' => 'a@qlinic.test', 'password' => 'secret123', 'role' => 'admin', 'is_active' => true]);
    }

    public function test_admin_can_add_a_service_with_a_unique_slug(): void
    {
        $this->actingAs($this->admin)->post('/admin/layanan', ['name' => 'Tumbuh Kembang', 'icon' => 'heart', 'sort_order' => 4])->assertSessionHas('status');
        $this->actingAs($this->admin)->post('/admin/layanan', ['name' => 'Tumbuh Kembang', 'icon' => 'heart'])->assertSessionHas('status');

        $this->assertSame(
            ['tumbuh-kembang', 'tumbuh-kembang-2'],
            Service::where('name', 'Tumbuh Kembang')->orderBy('id')->pluck('slug')->all()
        );
    }

    public function test_icon_must_be_known(): void
    {
        $this->actingAs($this->admin)->post('/admin/layanan', ['name' => 'X', 'icon' => 'rocket'])->assertSessionHasErrors('icon');
    }

    public function test_deactivated_service_disappears_from_home(): void
    {
        $service = Service::where('slug', 'imunisasi')->first();

        $this->actingAs($this->admin)->put("/admin/layanan/{$service->id}", ['name' => 'Imunisasi', 'icon' => 'syringe', 'sort_order' => 3])
            ->assertSessionHas('status'); // is_active tidak dikirim = nonaktif

        $this->assertFalse($service->refresh()->is_active);

        // Jangan assertDontSee('Imunisasi'): pesan flash "Layanan "Imunisasi" disimpan." masih tampil di request berikutnya.
        $this->get('/')
            ->assertOk()
            ->assertViewHas('services', fn ($services) => $services->pluck('slug')->doesntContain('imunisasi')
                && $services->pluck('slug')->contains('pemeriksaan-kesehatan'));
    }

    public function test_unused_service_can_be_deleted(): void
    {
        $service = Service::where('slug', 'imunisasi')->first();

        $this->actingAs($this->admin)->delete("/admin/layanan/{$service->id}")->assertSessionHas('status');

        $this->assertDatabaseMissing('services', ['id' => $service->id]);
    }

    public function test_service_used_by_a_ticket_cannot_be_deleted(): void
    {
        $service = Service::first();
        QueueTicket::create([
            'clinic_id' => Clinic::first()->id, 'service_id' => $service->id, 'practice_date' => '2026-11-18', 'session' => 'pagi',
            'number' => 1, 'patient_name' => 'A', 'phone' => '081234567890', 'birth_date' => '2020-01-01',
            'device_token' => str_repeat('a', 40), 'status' => 'menunggu',
        ]);

        $this->actingAs($this->admin)->delete("/admin/layanan/{$service->id}")->assertSessionHas('error');

        $this->assertDatabaseHas('services', ['id' => $service->id]);
    }
}