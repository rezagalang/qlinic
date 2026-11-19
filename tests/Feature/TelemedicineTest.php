<?php

namespace Tests\Feature;

use App\Models\Clinic;
use App\Models\TelemedicineSchedule;
use Carbon\Carbon;
use Database\Seeders\ClinicSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TelemedicineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ClinicSeeder::class); // telemedicine contoh: Senin-Jumat 13.00-15.00
    }

    public function test_page_lists_schedules_and_whatsapp_link(): void
    {
        $this->travelTo(Carbon::parse('2026-11-18 14:00:00')); // Rabu

        $this->get('/telemedicine')
            ->assertOk()
            ->assertSee('Jadwal Telemedicine')
            ->assertSee('13.00-15.00')
            ->assertSee('https://wa.me/6285239116000?text=', false)
            ->assertSee('Sedang buka sekarang');
    }

    public function test_page_reports_outside_hours(): void
    {
        $this->travelTo(Carbon::parse('2026-11-18 16:00:00'));

        $this->get('/telemedicine')->assertOk()->assertSee('Di luar jam telemedicine');
    }

    public function test_weekend_has_no_schedule(): void
    {
        $this->travelTo(Carbon::parse('2026-11-22 14:00:00')); // Minggu

        $this->get('/telemedicine')->assertOk()->assertSee('Di luar jam telemedicine');
    }

    public function test_home_shows_today_telemedicine_hours_and_links_to_the_page(): void
    {
        $this->travelTo(Carbon::parse('2026-11-18 10:00:00'));

        $this->get('/')
            ->assertOk()
            ->assertSee('Hari ini: 13.00-15.00')
            ->assertSee(route('telemedicine'), false);
    }

    public function test_home_says_when_there_is_no_schedule_today(): void
    {
        $this->travelTo(Carbon::parse('2026-11-22 10:00:00'));

        $this->get('/')->assertOk()->assertSee('Hari ini tidak ada jadwal');
    }

    public function test_home_embeds_the_map_only_when_configured(): void
    {
        $this->get('/')->assertOk()->assertSee('Peta lokasi belum diatur')->assertDontSee('<iframe', false);

        Clinic::first()->update(['map_embed_url' => 'https://www.google.com/maps/embed?pb=abc']);

        $this->get('/')->assertOk()
            ->assertSee('<iframe src="https://www.google.com/maps/embed?pb=abc"', false)
            ->assertDontSee('Peta lokasi belum diatur');
    }

    public function test_inactive_schedule_is_hidden(): void
    {
        TelemedicineSchedule::query()->update(['is_active' => false]);
        $this->travelTo(Carbon::parse('2026-11-18 14:00:00'));

        $this->get('/telemedicine')->assertOk()->assertDontSee('13.00-15.00')->assertSee('Di luar jam telemedicine');
    }
}