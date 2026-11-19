<?php

namespace App\Services;

use App\Enums\PracticeSession;
use App\Models\Clinic;
use App\Models\PracticeSchedule;
use App\Models\QueueTicket;
use App\Models\ScheduleException;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Menentukan status pendaftaran online per tanggal + sesi.
 *
 * Status: open | full | closed | not_yet | off
 *  - off     : tidak praktik (tidak ada jadwal / ditutup lewat schedule_exceptions / di luar rentang hari)
 *  - not_yet : belum dibuka (pendaftaran dibuka N jam sebelum jam praktik dimulai)
 *  - closed  : sudah lewat jam selesai praktik
 *  - full    : kuota sesi terpenuhi
 *  - open    : bisa mendaftar
 */
class RegistrationWindow
{
    /** Daftar hari (sesuai booking_days_ahead) beserta status tiap sesi. */
    public function days(Clinic $clinic, ?CarbonImmutable $now = null): array
    {
        $now = ($now ?? CarbonImmutable::now())->locale('id');
        $first = $now->startOfDay();
        $count = max(1, $clinic->booking_days_ahead);
        $last = $first->addDays($count - 1);

        $schedules = PracticeSchedule::query()
            ->where('clinic_id', $clinic->id)
            ->where('is_active', true)
            ->get()
            ->keyBy(fn (PracticeSchedule $s) => $s->day_of_week.'-'.$s->session->value);

        $exceptions = ScheduleException::query()
            ->where('clinic_id', $clinic->id)
            ->where('is_closed', true)
            ->whereBetween('date', [$first->toDateString(), $last->toDateString()])
            ->get();

        // Pakai query builder agar tidak terkena cast model pada kolom date/session.
        $counts = DB::table('queue_tickets')
            ->where('clinic_id', $clinic->id)
            ->where('status', '!=', 'dibatalkan')
            ->whereBetween('practice_date', [$first->toDateString(), $last->toDateString()])
            ->selectRaw('practice_date, session, count(*) as total')
            ->groupBy('practice_date', 'session')
            ->get()
            ->mapWithKeys(fn ($row) => [substr((string) $row->practice_date, 0, 10).'-'.$row->session => (int) $row->total]);

        $days = [];

        for ($i = 0; $i < $count; $i++) {
            $date = $first->addDays($i);
            $sessions = [];

            foreach (PracticeSession::cases() as $session) {
                $closed = $exceptions->contains(
                    fn (ScheduleException $e) => $e->date->toDateString() === $date->toDateString()
                        && ($e->session === null || $e->session === $session->value)
                );

                $sessions[] = $this->evaluate(
                    $schedules->get($date->dayOfWeekIso.'-'.$session->value),
                    $closed,
                    $counts->get($date->toDateString().'-'.$session->value, 0),
                    $date,
                    $session,
                    $now,
                    $clinic->registration_hours_before,
                );
            }

            $days[] = [
                'date' => $date->toDateString(),
                'weekday' => $date->translatedFormat('l'),
                'day' => $date->format('j'),
                'month' => $date->translatedFormat('F'),
                'sessions' => $sessions,
            ];
        }

        return $days;
    }

    /** Status satu tanggal + sesi (dipakai server-side saat pendaftaran). */
    public function check(Clinic $clinic, string $date, PracticeSession $session, ?CarbonImmutable $now = null): array
    {
        $now ??= CarbonImmutable::now();
        $day = CarbonImmutable::parse($date)->startOfDay();
        $today = $now->startOfDay();

        if ($day->lt($today) || $day->gt($today->addDays(max(1, $clinic->booking_days_ahead) - 1))) {
            return $this->evaluate(null, false, 0, $day, $session, $now, $clinic->registration_hours_before);
        }

        $schedule = PracticeSchedule::query()
            ->where('clinic_id', $clinic->id)
            ->where('day_of_week', $day->dayOfWeekIso)
            ->where('session', $session->value)
            ->where('is_active', true)
            ->first();

        $closed = ScheduleException::query()
            ->where('clinic_id', $clinic->id)
            ->where('date', $day->toDateString())
            ->where('is_closed', true)
            ->where(fn ($q) => $q->whereNull('session')->orWhere('session', $session->value))
            ->exists();

        $count = QueueTicket::query()
            ->where('clinic_id', $clinic->id)
            ->where('practice_date', $day->toDateString())
            ->where('session', $session->value)
            ->where('status', '!=', 'dibatalkan')
            ->count();

        return $this->evaluate($schedule, $closed, $count, $day, $session, $now, $clinic->registration_hours_before);
    }

    private function evaluate(
        ?PracticeSchedule $schedule,
        bool $closed,
        int $count,
        CarbonImmutable $date,
        PracticeSession $session,
        CarbonImmutable $now,
        int $hoursBefore,
    ): array {
        $base = ['key' => $session->value, 'name' => $session->label(), 'time' => null, 'status' => 'off'];

        if ($schedule === null || $closed) {
            return $base;
        }

        $startAt = $date->setTimeFromTimeString($schedule->start_time);
        $endAt = $date->setTimeFromTimeString($schedule->end_time);
        $opensAt = $startAt->subHours($hoursBefore);

        $base['time'] = $this->formatTime($schedule->start_time).' s/d '.$this->formatTime($schedule->end_time);

        $base['status'] = match (true) {
            $now->lt($opensAt) => 'not_yet',
            $now->gte($endAt) => 'closed',
            $schedule->quota !== null && $count >= $schedule->quota => 'full',
            default => 'open',
        };

        return $base;
    }

    private function formatTime(string $time): string
    {
        return CarbonImmutable::createFromTimeString($time)->format('G.i'); // 09:00:00 -> 9.00
    }
}
