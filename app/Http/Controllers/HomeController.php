<?php

namespace App\Http\Controllers;

use App\Models\Clinic;
use App\Models\Service;
use App\Models\TelemedicineSchedule;
use App\Services\RegistrationWindow;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function index(RegistrationWindow $window): View
    {
        $clinic = Clinic::query()->firstOrFail();

        return view('home', [
            'clinic' => $clinic,
            'services' => Service::query()->where('clinic_id', $clinic->id)->active()->get(),
            'telemedicineToday' => TelemedicineSchedule::query()
                ->where('clinic_id', $clinic->id)
                ->where('is_active', true)
                ->where('day_of_week', now()->dayOfWeekIso)
                ->orderBy('start_time')
                ->get(),
            'registrationDays' => $window->days($clinic),
            'registrationHoursBefore' => $clinic->registration_hours_before,
        ]);
    }
}