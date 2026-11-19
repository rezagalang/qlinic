<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Clinic;
use App\Models\TelemedicineSchedule;
use App\Support\Days;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TelemedicineScheduleController extends Controller
{
    public function index(): View
    {
        return view('admin.telemedicine.index', [
            'days' => Days::NAMES,
            'schedules' => TelemedicineSchedule::query()->orderBy('day_of_week')->orderBy('start_time')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'day_of_week' => ['required', 'integer', 'between:1,7'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'note' => ['nullable', 'string', 'max:100'],
        ], [], ['day_of_week' => 'hari', 'start_time' => 'jam mulai', 'end_time' => 'jam selesai', 'note' => 'catatan']);

        TelemedicineSchedule::create([
            'clinic_id' => Clinic::query()->firstOrFail()->id,
            'day_of_week' => $data['day_of_week'],
            'start_time' => $data['start_time'].':00',
            'end_time' => $data['end_time'].':00',
            'note' => $data['note'] ?? null,
            'is_active' => true,
        ]);

        return back()->with('status', 'Jadwal telemedicine ditambahkan.');
    }

    public function destroy(TelemedicineSchedule $schedule): RedirectResponse
    {
        $schedule->delete();

        return back()->with('status', 'Jadwal telemedicine dihapus.');
    }
}