<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PracticeSession;
use App\Enums\QueueStatus;
use App\Http\Controllers\Controller;
use App\Models\Clinic;
use App\Models\QueueTicket;
use App\Services\QueueManager;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class QueueController extends Controller
{
    public function index(Request $request): View
    {
        $clinic = Clinic::query()->firstOrFail();
        $date = $this->dateFrom($request->query('date'));
        $session = $this->sessionFrom($request->query('session'), $clinic->id, $date);

        $tickets = QueueTicket::query()
            ->with('service')
            ->where('clinic_id', $clinic->id)
            ->where('practice_date', $date)
            ->where('session', $session->value)
            ->orderBy('number')
            ->get();

        $counts = [];
        foreach (QueueStatus::cases() as $status) {
            $counts[$status->value] = $tickets->filter(fn (QueueTicket $t) => $t->status === $status)->count();
        }

        return view('admin.queue.index', [
            'date' => $date,
            'dateLabel' => CarbonImmutable::parse($date)->locale('id')->translatedFormat('l, j F Y'),
            'session' => $session,
            'tickets' => $tickets,
            'counts' => $counts,
            'current' => $tickets->first(fn (QueueTicket $t) => $t->status === QueueStatus::Dipanggil),
        ]);
    }

    public function next(Request $request, QueueManager $manager): RedirectResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'session' => ['required', Rule::enum(PracticeSession::class)],
        ]);

        $redirect = redirect()->route('admin.queue', ['date' => $data['date'], 'session' => $data['session']]);

        try {
            $ticket = $manager->callNext(
                Clinic::query()->firstOrFail(),
                $data['date'],
                PracticeSession::from($data['session']),
                $request->user(),
            );
        } catch (DomainException $e) {
            return $redirect->with('error', $e->getMessage());
        }

        return $redirect->with('status', 'Memanggil nomor '.$ticket->number.' ('.$ticket->patient_name.')');
    }

    public function action(Request $request, QueueTicket $ticket, string $action, QueueManager $manager): RedirectResponse
    {
        $redirect = redirect()->route('admin.queue', [
            'date' => $ticket->practice_date->toDateString(),
            'session' => $ticket->session->value,
        ]);

        try {
            $updated = $manager->apply($ticket, $action, $request->user());
        } catch (DomainException $e) {
            return $redirect->with('error', $e->getMessage());
        }

        return $redirect->with('status', 'Nomor '.$updated->number.' sekarang berstatus "'.$updated->status->label().'".');
    }

    private function dateFrom(mixed $value): string
    {
        if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) && strtotime($value) !== false) {
            return $value;
        }

        return today()->toDateString();
    }

    private function sessionFrom(mixed $value, int $clinicId, string $date): PracticeSession
    {
        if (is_string($value) && ($session = PracticeSession::tryFrom($value))) {
            return $session;
        }

        // Tanpa pilihan: sesi pertama yang sudah punya tiket pada tanggal tsb, atau Pagi.
        foreach (PracticeSession::cases() as $session) {
            $has = QueueTicket::query()
                ->where('clinic_id', $clinicId)
                ->where('practice_date', $date)
                ->where('session', $session->value)
                ->exists();

            if ($has) {
                return $session;
            }
        }

        return PracticeSession::Pagi;
    }
}