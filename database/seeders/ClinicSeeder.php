<?php

namespace Database\Seeders;

use App\Models\Clinic;
use App\Models\PracticeSchedule;
use App\Models\Service;
use App\Models\TelemedicineSchedule;
use Illuminate\Database\Seeder;

/** Idempotent: aman dijalankan ulang, tidak menimpa data yang sudah diubah dari admin. */
class ClinicSeeder extends Seeder
{
    public function run(): void
    {
        $clinic = Clinic::query()->first() ?? Clinic::create([
            'name' => 'dr. Naufal Baras, Sp.A',
            'specialty' => 'Klinik Anak',
            'address' => 'Jl Raden Wijaya, Giri - Banyuwangi',
            'whatsapp' => '6285239116000',
            'whatsapp_label' => '0852 3911 6000',
            'maps_url' => 'https://maps.google.com',
            'footer_text' => 'Qlinic.id! - Sistem Antrian Klinik',
            'registration_hours_before' => 3,
            'booking_days_ahead' => 4,
        ]);

        $services = [
            ['slug' => 'pemeriksaan-kesehatan', 'name' => 'Pemeriksaan Kesehatan', 'icon' => 'stethoscope', 'sort_order' => 1],
            ['slug' => 'konsultasi-kesehatan', 'name' => 'Konsultasi Kesehatan', 'icon' => 'heart', 'sort_order' => 2],
            ['slug' => 'imunisasi', 'name' => 'Imunisasi', 'icon' => 'syringe', 'sort_order' => 3],
        ];

        foreach ($services as $service) {
            Service::firstOrCreate(['slug' => $service['slug']], $service + ['clinic_id' => $clinic->id]);
        }

        // Contoh jadwal: Senin-Sabtu, sesi Pagi dan Malam (Sore tidak praktik). Ubah dari admin.
        foreach (range(1, 6) as $day) {
            foreach ([['pagi', '09:00:00', '12:30:00'], ['malam', '19:00:00', '21:30:00']] as [$session, $start, $end]) {
                PracticeSchedule::firstOrCreate(
                    ['clinic_id' => $clinic->id, 'day_of_week' => $day, 'session' => $session],
                    ['start_time' => $start, 'end_time' => $end, 'quota' => null, 'is_active' => true],
                );
            }
        }

        // Contoh jadwal telemedicine (hanya jika masih kosong).
        if (! TelemedicineSchedule::query()->exists()) {
            foreach (range(1, 5) as $day) {
                TelemedicineSchedule::create([
                    'clinic_id' => $clinic->id,
                    'day_of_week' => $day,
                    'start_time' => '13:00:00',
                    'end_time' => '15:00:00',
                    'note' => 'Khusus pasien sakit',
                ]);
            }
        }
    }
}
