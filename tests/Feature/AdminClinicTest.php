<?php

namespace Tests\Feature;

use App\Models\Clinic;
use App\Models\User;
use Database\Seeders\ClinicSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminClinicTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ClinicSeeder::class);
        $this->admin = User::create(['name' => 'Admin', 'email' => 'a@qlinic.test', 'password' => 'secret123', 'role' => 'admin', 'is_active' => true]);
    }

    private function payload(array $override = []): array
    {
        return array_merge([
            'name' => 'dr. Contoh, Sp.A',
            'specialty' => 'Klinik Anak',
            'address' => 'Jl. Contoh 1',
            'whatsapp' => '0852 3911 6000',
            'maps_url' => 'https://maps.app.goo.gl/abc',
            'map_embed_url' => '',
            'footer_text' => 'Footer',
            'registration_hours_before' => '2',
            'booking_days_ahead' => '5',
        ], $override);
    }

    public function test_admin_can_open_and_update_the_profile(): void
    {
        $this->actingAs($this->admin)->get('/admin/klinik')->assertOk()->assertSee('Profil Klinik');

        $this->actingAs($this->admin)->put('/admin/klinik', $this->payload())->assertSessionHas('status');

        $clinic = Clinic::first();
        $this->assertSame('dr. Contoh, Sp.A', $clinic->name);
        $this->assertSame(2, $clinic->registration_hours_before);
        $this->assertSame(5, $clinic->booking_days_ahead);
    }

    public function test_whatsapp_is_normalised_and_label_is_derived(): void
    {
        $this->actingAs($this->admin)->put('/admin/klinik', $this->payload(['whatsapp' => '+62 852-3911-6000']));

        $clinic = Clinic::first();
        $this->assertSame('6285239116000', $clinic->whatsapp);
        $this->assertSame('0852 3911 6000', $clinic->whatsapp_label);
    }

    public function test_invalid_whatsapp_is_rejected(): void
    {
        $this->actingAs($this->admin)->put('/admin/klinik', $this->payload(['whatsapp' => '12345']))->assertSessionHasErrors('whatsapp');
    }

    public function test_iframe_code_is_reduced_to_its_src(): void
    {
        $iframe = '<iframe src="https://www.google.com/maps/embed?pb=!1m18" width="600" height="450" loading="lazy"></iframe>';

        $this->actingAs($this->admin)->put('/admin/klinik', $this->payload(['map_embed_url' => $iframe]))->assertSessionHasNoErrors();

        $this->assertSame('https://www.google.com/maps/embed?pb=!1m18', Clinic::first()->map_embed_url);
    }

    public function test_non_google_embed_and_http_links_are_rejected(): void
    {
        $this->actingAs($this->admin)->put('/admin/klinik', $this->payload(['map_embed_url' => 'https://evil.example/maps/embed']))
            ->assertSessionHasErrors('map_embed_url');
        $this->actingAs($this->admin)->put('/admin/klinik', $this->payload(['map_embed_url' => 'https://www.google.com/search?q=x']))
            ->assertSessionHasErrors('map_embed_url');
        $this->actingAs($this->admin)->put('/admin/klinik', $this->payload(['maps_url' => 'http://maps.example.com']))
            ->assertSessionHasErrors('maps_url');
    }

    public function test_booking_settings_are_bounded(): void
    {
        $this->actingAs($this->admin)->put('/admin/klinik', $this->payload(['registration_hours_before' => '99', 'booking_days_ahead' => '0']))
            ->assertSessionHasErrors(['registration_hours_before', 'booking_days_ahead']);
    }

    public function test_petugas_cannot_edit_the_profile(): void
    {
        $petugas = User::create(['name' => 'P', 'email' => 'p@qlinic.test', 'password' => 'secret123', 'role' => 'petugas', 'is_active' => true]);

        $this->actingAs($petugas)->get('/admin/klinik')->assertForbidden();
        $this->actingAs($petugas)->put('/admin/klinik', $this->payload())->assertForbidden();
    }
}