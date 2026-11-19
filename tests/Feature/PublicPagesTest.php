<?php

namespace Tests\Feature;

use Database\Seeders\ClinicSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ClinicSeeder::class);
    }

    public function test_home_page_renders_clinic_data(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Daftar Antrian Online')
            ->assertSee('Kontak Layanan Klinik')
            ->assertSee('dr. Naufal Baras, Sp.A')
            ->assertSee('Imunisasi');
    }

    public function test_my_queue_page_shows_empty_state(): void
    {
        $this->get('/antrian')
            ->assertOk()
            ->assertSee('Anda belum memiliki nomor antrian')
            ->assertSee('Lihat nomor antrian saya');
    }
}
