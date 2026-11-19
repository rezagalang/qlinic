<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PracticeSession;
use App\Http\Controllers\Controller;
use App\Models\Clinic;
use App\Models\PracticeSchedule;
use App\Models\ScheduleException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ScheduleController extends Controller
{
    public const DAYS = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];

    public function index(): View
    {
        $clinic = Clinic::query()->firstOrFail();

        return view('admin.schedule.index', [
            'days' => self::DAYS,
            'sessions' => PracticeSession::cases(),
            'schedules' => PracticeSchedule::query()
                ->where('clinic_id', $clinic->id)
                ->get()
                ->keyBy(fn (PracticeSchedule $s) => $s->day_of_week.'-'.$s->session->value),
            'exceptions' => ScheduleException::query()
                ->where('clinic_id', $clinic->id)
                ->where('date', '>=', today()->toDateString())
                ->orderBy('date')
                ->get(),
        ]);
    }

    /** Simpan seluruh jadwal mingguan sekaligus. Baris yang tidak dicentang dinonaktifkan. */
    public function update(Request $request): RedirectResponse
    {
        $clinic = Clinic::query()->firstOrFail();
        $input = $request->input('schedules', []);
        $rows = [];
        $errors = [];

        foreach (array_keys(self::DAYS) as $day) {
            foreach (PracticeSession::cases() as $session) {
                $row = $input[$day][$session->value] ?? [];

                if (empty($row['enabled'])) {
                    $rows[] = [$day, $session, null];

                    continue;
                }

                $validator = Validator::make($row, [
                    'start_time' => ['required', 'date_format:H:i'],
                    'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
                    'quota' => ['nullable', 'integer', 'min:1', 'max:500'],
                ], [], ['start_time' => 'jam mulai', 'end_time' => 'jam selesai', 'quota' => 'kuota']);

                if ($validator->fails()) {
                    foreach ($validator->errors()->messages() as $field => $messages) {
                        $errors["schedules.$day.{$session->value}.$field"] = $messages[0];
                    }

                    continue;
                }

                $rows[] = [$day, $session, $validator->validated()];
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        DB::transaction(function () use ($rows, $clinic) {
            foreach ($rows as [$day, $session, $data]) {
                $key = ['clinic_id' => $clinic->id, 'day_of_week' => $day, 'session' => $session->value];

                if ($data === null) {
                    PracticeSchedule::query()->where($key)->update(['is_active' => false]);

                    continue;
                }

                PracticeSchedule::updateOrCreate($key, [
                    'start_time' => $data['start_time'].':00',
                    'end_time' => $data['end_time'].':00',
                    'quota' => $data['quota'] ?? null,
                    'is_active' => true,
                ]);
            }
        });

        return back()->with('status', 'Jadwal praktik disimpan.');
    }

    public function storeException(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'session' => ['nullable', Rule::enum(PracticeSession::class)],
            'note' => ['nullable', 'string', 'max:150'],
        ]);

        ScheduleException::firstOrCreate(
            ['clinic_id' => Clinic::query()->firstOrFail()->id, 'date' => $data['date'], 'session' => $data['session'] ?? null],
            ['is_closed' => true, 'note' => $data['note'] ?? null],
        );

        return back()->with('status', 'Hari libur / penutupan ditambahkan.');
    }

    public function destroyException(ScheduleException $exception): RedirectResponse
    {
        $exception->delete();

        return back()->with('status', 'Penutupan dihapus.');
    }
}