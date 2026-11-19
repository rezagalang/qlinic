<?php

namespace App\Http\Controllers;

use App\Enums\PracticeSession;
use App\Models\Clinic;
use App\Models\QueueTicket;
use App\Services\RegistrationWindow;
use App\Support\DeviceToken;
use App\Support\Phone;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;

class MyQueueController extends Controller
{
    public function index(Request $request, RegistrationWindow $window): View
    {
        $clinic = Clinic::query()->firstOrFail();
        $token = DeviceToken::fromRequest($request);

        return view('queue.index', [
            'tickets' => $token ? $this->ticketsFor($token) : collect(),
            'registrationDays' => $window->days($clinic),
            'registrationHoursBefore' => $clinic->registration_hours_before,
        ]);
    }

    /** Data ringan untuk polling halaman Antrian Saya (nomor dipanggil + sisa antrian). */
    public function status(Request $request): JsonResponse
    {
        $token = DeviceToken::fromRequest($request);
        $tickets = $token ? $this->ticketsFor($token) : collect();

        return response()->json([
            'tickets' => $tickets->map(fn (QueueTicket $t) => [
                'id' => $t->id,
                'status' => $t->status->value,
                'status_label' => $t->status->label(),
                'current' => $t->currentNumber() ?? '-',
                'ahead' => $t->aheadCount(),
            ])->values(),
        ]);
    }

    /** Pulihkan nomor antrian (ganti HP) memakai nomor HP + tanggal lahir. */
    public function recover(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'phone' => ['required', 'string', 'max:20'],
            'birth_date' => ['required', 'date_format:Y-m-d'],
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator, 'recover')->withInput()->with('openRecover', true);
        }

        $tickets = QueueTicket::query()
            ->where('phone', Phone::normalize($request->input('phone')))
            ->where('birth_date', $request->input('birth_date'))
            ->where('practice_date', '>=', today()->toDateString())
            ->where('status', '!=', 'dibatalkan')
            ->get();

        if ($tickets->isEmpty()) {
            return back()
                ->withErrors(['phone' => 'Data antrian tidak ditemukan. Periksa nomor HP dan tanggal lahir.'], 'recover')
                ->withInput()
                ->with('openRecover', true);
        }

        $token = DeviceToken::fromRequest($request) ?? DeviceToken::generate();
        QueueTicket::query()->whereIn('id', $tickets->pluck('id'))->update(['device_token' => $token]);

        return redirect()
            ->route('queue.index')
            ->with('status', 'Nomor antrian Anda berhasil ditampilkan kembali.')
            ->withCookie(DeviceToken::cookie($token));
    }

    private function ticketsFor(string $token): Collection
    {
        $order = array_column(PracticeSession::cases(), 'value');

        return QueueTicket::query()
            ->with('service')
            ->where('device_token', $token)
            ->where('practice_date', '>=', today()->toDateString())
            ->where('status', '!=', 'dibatalkan')
            ->get()
            ->sortBy(fn (QueueTicket $t) => $t->practice_date->format('Ymd').array_search($t->session->value, $order))
            ->values();
    }
}