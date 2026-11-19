<?php

namespace App\Models;

use App\Enums\PracticeSession;
use App\Enums\QueueStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'clinic_id', 'service_id', 'practice_date', 'session', 'number', 'patient_name', 'phone',
    'birth_date', 'gender', 'address', 'complaint', 'device_token', 'status', 'called_at', 'finished_at', 'handled_by',
])]
class QueueTicket extends Model
{
    protected function casts(): array
    {
        return [
            // format eksplisit agar tersimpan 'Y-m-d' (aman untuk MySQL maupun SQLite)
            'practice_date' => 'date:Y-m-d',
            'birth_date' => 'date:Y-m-d',
            'session' => PracticeSession::class,
            'status' => QueueStatus::class,
            'number' => 'integer',
            'called_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /** Jumlah antrian yang masih menunggu di depan tiket ini (sesi + tanggal yang sama). */
    public function aheadCount(): int
    {
        return static::query()
            ->where('clinic_id', $this->clinic_id)
            ->where('practice_date', $this->practice_date->toDateString())
            ->where('session', $this->session->value)
            ->where('number', '<', $this->number)
            ->where('status', QueueStatus::Menunggu->value)
            ->count();
    }

    /** Nomor yang sedang dipanggil saat ini pada sesi + tanggal yang sama (null jika belum ada). */
    public function currentNumber(): ?int
    {
        $number = static::query()
            ->where('clinic_id', $this->clinic_id)
            ->where('practice_date', $this->practice_date->toDateString())
            ->where('session', $this->session->value)
            ->where('status', QueueStatus::Dipanggil->value)
            ->max('number');

        return $number === null ? null : (int) $number;
    }
}