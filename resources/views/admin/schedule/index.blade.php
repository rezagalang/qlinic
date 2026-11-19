<x-layouts.admin title="Jadwal">
    <h1 class="text-lg font-semibold">Jadwal Praktik</h1>
    <p class="mt-1 text-sm text-slate-500">Centang sesi yang praktik, lalu atur jam dan kuota (kosongkan kuota jika tanpa batas).</p>

    <form method="POST" action="{{ route('admin.schedule.update') }}" class="mt-4 space-y-3">
        @csrf
        @method('PUT')

        @foreach ($days as $dayNo => $dayName)
            <div class="rounded-lg bg-white p-4 shadow-sm">
                <h2 class="mb-2 text-sm font-semibold text-brand-700">{{ $dayName }}</h2>

                @foreach ($sessions as $session)
                    @php
                        $schedule = $schedules->get($dayNo.'-'.$session->value);
                        $base = "schedules.$dayNo.{$session->value}";
                        $enabled = old('schedules') !== null ? ! empty(old("$base.enabled")) : (bool) ($schedule?->is_active);
                        $start = old("$base.start_time", $schedule ? substr($schedule->start_time, 0, 5) : '');
                        $end = old("$base.end_time", $schedule ? substr($schedule->end_time, 0, 5) : '');
                        $quota = old("$base.quota", $schedule?->quota);
                    @endphp
                    <div class="grid grid-cols-2 items-end gap-2 border-t border-slate-100 py-2 text-sm first:border-t-0 sm:grid-cols-[8rem_1fr_1fr_1fr]">
                        <label class="col-span-2 flex items-center gap-2 font-medium sm:col-span-1">
                            <input type="checkbox" name="schedules[{{ $dayNo }}][{{ $session->value }}][enabled]" value="1" class="accent-brand-500" @checked($enabled)>
                            {{ $session->label() }}
                        </label>
                        <label class="text-xs">Mulai
                            <input type="time" name="schedules[{{ $dayNo }}][{{ $session->value }}][start_time]" value="{{ $start }}"
                                   class="mt-0.5 w-full rounded-lg border border-slate-300 px-2 py-1.5 text-sm">
                        </label>
                        <label class="text-xs">Selesai
                            <input type="time" name="schedules[{{ $dayNo }}][{{ $session->value }}][end_time]" value="{{ $end }}"
                                   class="mt-0.5 w-full rounded-lg border border-slate-300 px-2 py-1.5 text-sm">
                        </label>
                        <label class="text-xs">Kuota
                            <input type="number" min="1" max="500" name="schedules[{{ $dayNo }}][{{ $session->value }}][quota]" value="{{ $quota }}" placeholder="tanpa batas"
                                   class="mt-0.5 w-full rounded-lg border border-slate-300 px-2 py-1.5 text-sm">
                        </label>

                        @foreach (['start_time', 'end_time', 'quota'] as $field)
                            @if ($errors->has("$base.$field"))
                                <p class="col-span-2 text-xs text-red-600 sm:col-span-4">{{ $session->label() }}: {{ $errors->first("$base.$field") }}</p>
                            @endif
                        @endforeach
                    </div>
                @endforeach
            </div>
        @endforeach

        <button type="submit" class="rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-semibold text-white">Simpan jadwal</button>
    </form>

    {{-- Libur / penutupan --}}
    <h2 class="mt-8 text-lg font-semibold">Libur &amp; Penutupan</h2>

    <form method="POST" action="{{ route('admin.schedule.exception.store') }}" class="mt-3 flex flex-wrap items-end gap-3 rounded-lg bg-white p-4 shadow-sm">
        @csrf
        <label class="text-sm">
            <span class="block font-medium">Tanggal</span>
            <input type="date" name="date" min="{{ today()->toDateString() }}" required value="{{ old('date') }}"
                   class="mt-1 rounded-lg border border-slate-300 px-3 py-2">
            @error('date') <span class="block text-xs text-red-600">{{ $message }}</span> @enderror
        </label>
        <label class="text-sm">
            <span class="block font-medium">Sesi</span>
            <select name="session" class="mt-1 rounded-lg border border-slate-300 px-3 py-2">
                <option value="">Semua sesi</option>
                @foreach ($sessions as $s)
                    <option value="{{ $s->value }}" @selected(old('session') === $s->value)>{{ $s->label() }}</option>
                @endforeach
            </select>
        </label>
        <label class="min-w-[12rem] flex-1 text-sm">
            <span class="block font-medium">Keterangan</span>
            <input type="text" name="note" maxlength="150" value="{{ old('note') }}" placeholder="mis. Cuti bersama"
                   class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
        </label>
        <button type="submit" class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-semibold text-white">Tambah</button>
    </form>

    <div class="mt-3 space-y-2">
        @forelse ($exceptions as $exception)
            <div class="flex items-center justify-between gap-3 rounded-lg bg-white px-4 py-3 text-sm shadow-sm">
                <div>
                    <span class="font-semibold">{{ $exception->date->locale('id')->translatedFormat('l, j F Y') }}</span>
                    &middot; {{ $exception->session ? ucfirst($exception->session) : 'Semua sesi' }}
                    @if ($exception->note) <span class="text-slate-500">&middot; {{ $exception->note }}</span> @endif
                </div>
                <form method="POST" action="{{ route('admin.schedule.exception.destroy', ['exception' => $exception->id]) }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="rounded-lg bg-red-100 px-3 py-1.5 text-xs font-semibold text-red-700">Hapus</button>
                </form>
            </div>
        @empty
            <div class="rounded-lg bg-white px-4 py-6 text-center text-sm text-slate-500 shadow-sm">Tidak ada penutupan terjadwal.</div>
        @endforelse
    </div>
</x-layouts.admin>