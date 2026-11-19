<?php

namespace App\Services;

use App\Enums\PracticeSession;
use App\Enums\QueueStatus;
use App\Models\Clinic;
use App\Models\QueueTicket;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QueueRegistrar
{
    public function __construct(private RegistrationWindow $window) {}

    /**
     * Membuat tiket antrian. Seluruh proses dikunci per klinik di dalam transaksi
     * sehingga nomor urut tidak ganda (unique key pada queue_tickets sebagai pengaman kedua).
     *
     * @param  array{date:string,session:string,service_id:int,patient_name:string,phone:string,birth_date:string,gender:string,address:string,complaint?:?string}  $data  phone sudah dinormalisasi
     *
     * @throws ValidationException
     */
    public function register(Clinic $clinic, array $data, string $deviceToken): QueueTicket
    {
        $session = PracticeSession::from($data['session']);

        return DB::transaction(function () use ($clinic, $data, $session, $deviceToken) {
            Clinic::query()->whereKey($clinic->id)->lockForUpdate()->first();

            $check = $this->window->check($clinic, $data['date'], $session);

            if ($check['status'] !== 'open') {
                throw ValidationException::withMessages(['session' => $this->messageFor($check['status'])]);
            }

            $scope = QueueTicket::query()
                ->where('clinic_id', $clinic->id)
                ->where('practice_date', $data['date'])
                ->where('session', $session->value);

            $duplicate = (clone $scope)
                ->where('phone', $data['phone'])
                ->where('birth_date', $data['birth_date'])
                ->whereIn('status', QueueStatus::activeValues())
                ->exists();

            if ($duplicate) {
                throw ValidationException::withMessages([
                    'phone' => 'Nomor HP dengan tanggal lahir ini sudah terdaftar pada sesi tersebut.',
                ]);
            }

            // Pasien yang sebelumnya dilewati dan mendaftar lagi di sesi yang sama: tiket lama
            // dibatalkan agar satu orang tidak memegang dua nomor aktif.
            (clone $scope)
                ->where('phone', $data['phone'])
                ->where('birth_date', $data['birth_date'])
                ->where('status', QueueStatus::Dilewati->value)
                ->update(['status' => QueueStatus::Dibatalkan->value]);

            $number = ((int) (clone $scope)->max('number')) + 1;

            return QueueTicket::create([
                'clinic_id' => $clinic->id,
                'service_id' => $data['service_id'],
                'practice_date' => $data['date'],
                'session' => $session->value,
                'number' => $number,
                'patient_name' => $data['patient_name'],
                'phone' => $data['phone'],
                'birth_date' => $data['birth_date'],
                'gender' => $data['gender'],
                'address' => $data['address'],
                'complaint' => $data['complaint'] ?? null,
                'device_token' => $deviceToken,
                'status' => QueueStatus::Menunggu->value,
            ]);
        });
    }

    private function messageFor(string $status): string
    {
        return match ($status) {
            'not_yet' => 'Pendaftaran online untuk sesi ini belum dibuka.',
            'closed' => 'Pendaftaran online untuk sesi ini sudah ditutup.',
            'full' => 'Kuota antrian untuk sesi ini sudah penuh.',
            default => 'Tidak ada praktik pada tanggal dan sesi tersebut.',
        };
    }
}