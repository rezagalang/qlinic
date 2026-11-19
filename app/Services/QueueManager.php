<?php

namespace App\Services;

use App\Enums\PracticeSession;
use App\Enums\QueueStatus;
use App\Models\Clinic;
use App\Models\QueueTicket;
use App\Models\User;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Transisi status antrian oleh petugas.
 *
 *  call    : menunggu | dilewati  -> dipanggil   (hanya satu nomor dipanggil per sesi)
 *  finish  : dipanggil            -> selesai
 *  skip    : menunggu | dipanggil -> dilewati
 *  cancel  : menunggu | dilewati  -> dibatalkan
 *  restore : dilewati             -> menunggu
 *
 * Pelanggaran aturan melempar DomainException (pesan siap ditampilkan).
 */
class QueueManager
{
    public const ACTIONS = ['call', 'finish', 'skip', 'cancel', 'restore'];

    public function callNext(Clinic $clinic, string $date, PracticeSession $session, User $by): QueueTicket
    {
        return DB::transaction(function () use ($clinic, $date, $session, $by) {
            $this->lock($clinic->id);
            $this->assertNoActiveCall($clinic->id, $date, $session->value);

            $ticket = $this->scope($clinic->id, $date, $session->value)
                ->where('status', QueueStatus::Menunggu->value)
                ->orderBy('number')
                ->first();

            if ($ticket === null) {
                throw new DomainException('Tidak ada antrian yang menunggu.');
            }

            return $this->markCalled($ticket, $by);
        });
    }

    public function apply(QueueTicket $ticket, string $action, User $by): QueueTicket
    {
        return DB::transaction(function () use ($ticket, $action, $by) {
            $this->lock($ticket->clinic_id);
            $ticket = $ticket->fresh();

            return match ($action) {
                'call' => $this->call($ticket, $by),
                'finish' => $this->move($ticket, [QueueStatus::Dipanggil], QueueStatus::Selesai, [
                    'finished_at' => now(),
                    'handled_by' => $by->id,
                ]),
                'skip' => $this->move($ticket, [QueueStatus::Menunggu, QueueStatus::Dipanggil], QueueStatus::Dilewati, [
                    'handled_by' => $by->id,
                ]),
                'cancel' => $this->move($ticket, [QueueStatus::Menunggu, QueueStatus::Dilewati], QueueStatus::Dibatalkan, [
                    'handled_by' => $by->id,
                ]),
                'restore' => $this->move($ticket, [QueueStatus::Dilewati], QueueStatus::Menunggu, [
                    'called_at' => null,
                ]),
                default => throw new DomainException('Aksi tidak dikenal.'),
            };
        });
    }

    private function call(QueueTicket $ticket, User $by): QueueTicket
    {
        $this->assertFrom($ticket, [QueueStatus::Menunggu, QueueStatus::Dilewati], QueueStatus::Dipanggil);
        $this->assertNoActiveCall(
            $ticket->clinic_id,
            $ticket->practice_date->toDateString(),
            $ticket->session->value,
            $ticket->id,
        );

        return $this->markCalled($ticket, $by);
    }

    private function markCalled(QueueTicket $ticket, User $by): QueueTicket
    {
        $ticket->update([
            'status' => QueueStatus::Dipanggil->value,
            'called_at' => now(),
            'finished_at' => null,
            'handled_by' => $by->id,
        ]);

        return $ticket;
    }

    /** @param  array<int, QueueStatus>  $from */
    private function move(QueueTicket $ticket, array $from, QueueStatus $to, array $attributes = []): QueueTicket
    {
        $this->assertFrom($ticket, $from, $to);
        $ticket->update(['status' => $to->value] + $attributes);

        return $ticket;
    }

    /** @param  array<int, QueueStatus>  $from */
    private function assertFrom(QueueTicket $ticket, array $from, QueueStatus $to): void
    {
        if (! in_array($ticket->status, $from, true)) {
            throw new DomainException(sprintf(
                'Nomor %d berstatus "%s" sehingga tidak dapat diubah menjadi "%s".',
                $ticket->number,
                $ticket->status->label(),
                $to->label(),
            ));
        }
    }

    private function assertNoActiveCall(int $clinicId, string $date, string $session, ?int $ignoreId = null): void
    {
        $called = $this->scope($clinicId, $date, $session)
            ->where('status', QueueStatus::Dipanggil->value)
            ->when($ignoreId, fn (Builder $q) => $q->where('id', '!=', $ignoreId))
            ->first();

        if ($called !== null) {
            throw new DomainException(sprintf(
                'Nomor %d sedang dipanggil. Selesaikan atau lewati terlebih dahulu.',
                $called->number,
            ));
        }
    }

    private function scope(int $clinicId, string $date, string $session): Builder
    {
        return QueueTicket::query()
            ->where('clinic_id', $clinicId)
            ->where('practice_date', $date)
            ->where('session', $session);
    }

    /** Serialisasi per klinik agar dua petugas tidak memanggil nomor berbeda bersamaan. */
    private function lock(int $clinicId): void
    {
        Clinic::query()->whereKey($clinicId)->lockForUpdate()->first();
    }
}