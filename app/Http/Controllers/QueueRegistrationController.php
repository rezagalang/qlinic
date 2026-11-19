<?php

namespace App\Http\Controllers;

use App\Enums\PracticeSession;
use App\Models\Clinic;
use App\Models\Service;
use App\Services\QueueRegistrar;
use App\Services\RegistrationWindow;
use App\Support\DeviceToken;
use App\Support\Phone;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\View\View;

class QueueRegistrationController extends Controller
{
    public function create(Request $request, RegistrationWindow $window): View|RedirectResponse
    {
        $validator = Validator::make($request->query(), [
            'date' => ['required', 'date_format:Y-m-d'],
            'session' => ['required', Rule::enum(PracticeSession::class)],
        ]);

        if ($validator->fails()) {
            return redirect()->route('home')->with('error', 'Pilih tanggal dan sesi terlebih dahulu.');
        }

        $clinic = Clinic::query()->firstOrFail();
        $session = PracticeSession::from($request->query('session'));
        $date = $request->query('date');
        $check = $window->check($clinic, $date, $session);

        if ($check['status'] !== 'open') {
            return redirect()->route('home')->with('error', 'Sesi tersebut tidak dapat didaftarkan saat ini.');
        }

        return view('queue.register', [
            'clinic' => $clinic,
            'services' => Service::query()->where('clinic_id', $clinic->id)->active()->get(),
            'date' => $date,
            'dateLabel' => CarbonImmutable::parse($date)->locale('id')->translatedFormat('l, d-m-Y'),
            'session' => $session,
            'time' => $check['time'],
            'registrationDays' => $window->days($clinic),
            'registrationHoursBefore' => $clinic->registration_hours_before,
        ]);
    }

    public function store(Request $request, QueueRegistrar $registrar): RedirectResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'session' => ['required', Rule::enum(PracticeSession::class)],
            'phone' => ['required', 'string', 'max:20'],
            'patient_name' => ['required', 'string', 'max:100'],
            'gender' => ['required', Rule::in(['L', 'P'])],
            'birth_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'address' => ['required', 'string', 'max:255'],
            'service_id' => ['required', 'integer', Rule::exists('services', 'id')->where('is_active', true)],
            'complaint' => ['nullable', 'string', 'max:500'],
        ], [
            'required' => ':attribute wajib diisi.',
            'max.string' => ':attribute maksimal :max karakter.',
            'date_format' => 'Format :attribute tidak valid.',
            'before_or_equal' => ':attribute tidak boleh melewati hari ini.',
            'in' => ':attribute tidak valid.',
            'exists' => ':attribute tidak valid.',
        ], [
            'phone' => 'No. handphone',
            'patient_name' => 'Nama pasien',
            'gender' => 'Jenis kelamin',
            'birth_date' => 'Tanggal lahir',
            'address' => 'Alamat pasien',
            'service_id' => 'Layanan',
            'complaint' => 'Keluhan',
        ]);

        $data['phone'] = Phone::normalize($data['phone']);

        if (! Phone::isValid($data['phone'])) {
            throw ValidationException::withMessages(['phone' => 'Nomor HP tidak valid. Contoh: 081234567890.']);
        }

        $token = DeviceToken::fromRequest($request) ?? DeviceToken::generate();
        $ticket = $registrar->register(Clinic::query()->firstOrFail(), $data, $token);

        return redirect()
            ->route('queue.index')
            ->with('status', 'Pendaftaran berhasil. Nomor antrian Anda: '.$ticket->number)
            ->withCookie(DeviceToken::cookie($token));
    }
}