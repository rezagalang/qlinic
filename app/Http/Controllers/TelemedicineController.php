<?php

namespace App\Http\Controllers;

use App\Models\Clinic;
use App\Models\TelemedicineSchedule;
use App\Services\RegistrationWindow;
use App\Support\Days;
use Illuminate\Contracts\View\View;

class TelemedicineController extends Controller
{
    public function index(RegistrationWindow $window): View
    {
        $clinic = Clinic::query()->firstOrFail();
        $now = now();
        $time = $now->format('H:i:s');

        $schedules = TelemedicineSchedule::query()
            ->where('clinic_id', $clinic->id)
            ->where('is_active', true)
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        $openNow = $schedules->contains(
            fn (TelemedicineSchedule $s) => $s->day_of_week === $now->dayOfWeekIso && $time >= $s->start_time && $time < $s->end_time
        );

        return view('telemedicine.index', [
            'clinic' => $clinic,
            'days' => Days::NAMES,
            'byDay' => $schedules->groupBy('day_of_week'),
            'today' => $now->dayOfWeekIso,
            'openNow' => $openNow,
            'whatsappUrl' => $clinic->whatsapp
                ? 'https://wa.me/'.$clinic->whatsapp.'?text='.rawurlencode('Halo '.$clinic->name.', saya ingin konsultasi telemedicine.')
                : null,
            'registrationDays' => $window->days($clinic),
            'registrationHoursBefore' => $clinic->registration_hours_before,
        ]);
    }
}