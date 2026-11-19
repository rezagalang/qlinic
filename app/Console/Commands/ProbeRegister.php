<?php

namespace App\Console\Commands;

use App\Models\Clinic;
use App\Models\Service;
use App\Services\QueueRegistrar;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;

/**
 * Alat uji konkurensi (Phase 8): mendaftarkan satu pasien uji lewat QueueRegistrar.
 * Dijalankan banyak proses sekaligus oleh tests/Concurrency. Hanya aktif di local/testing.
 */
class ProbeRegister extends Command
{
    protected $signature = 'qlinic:probe-register
        {date : Tanggal praktik Y-m-d}
        {session : pagi|sore|malam}
        {index : Nomor urut pasien uji (membuat nomor HP unik)}
        {--now= : Waktu palsu, mis. "2026-11-18 08:00:00"}
        {--start-at= : Unix timestamp (float) untuk menunggu sebelum mendaftar}';

    protected $description = 'Daftarkan satu pasien uji (khusus uji konkurensi; hanya local/testing).';

    public function handle(QueueRegistrar $registrar): int
    {
        if (! app()->environment('local', 'testing')) {
            $this->error('Perintah ini hanya untuk lingkungan local/testing.');

            return self::FAILURE;
        }

        if ($now = $this->option('now')) {
            $fake = Carbon::parse($now);
            Carbon::setTestNow($fake);
            CarbonImmutable::setTestNow($fake);
        }

        // Semua proses menunggu titik waktu yang sama agar benar-benar bersamaan setelah boot.
        if ($startAt = (float) $this->option('start-at')) {
            while (($wait = $startAt - microtime(true)) > 0) {
                usleep((int) min($wait * 1_000_000, 50_000));
            }
        }

        try {
            $ticket = $registrar->register(Clinic::query()->firstOrFail(), [
                'date' => $this->argument('date'),
                'session' => $this->argument('session'),
                'service_id' => Service::query()->firstOrFail()->id,
                'patient_name' => 'Pasien Uji '.$this->argument('index'),
                'phone' => '0812'.str_pad((string) $this->argument('index'), 8, '0', STR_PAD_LEFT),
                'birth_date' => '2020-01-01',
                'gender' => 'L',
                'address' => 'Alamat uji',
            ], str_repeat('p', 40));
        } catch (ValidationException $e) {
            $this->line('REJECTED='.implode('|', $e->validator->errors()->all()));

            return 2;
        }

        $this->line('NUMBER='.$ticket->number);

        return self::SUCCESS;
    }
}