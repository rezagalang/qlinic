@php $input = 'mt-1 rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-100'; @endphp
<x-layouts.admin title="Telemedicine">
    <h1 class="text-lg font-semibold">Jadwal Telemedicine</h1>

    <form method="POST" action="{{ route('admin.telemedicine.store') }}" class="mt-4 flex flex-wrap items-end gap-3 rounded-lg bg-white p-4 shadow-sm">
        @csrf
        <label class="text-sm">
            <span class="block font-medium">Hari</span>
            <select name="day_of_week" class="{{ $input }}">
                @foreach ($days as $no => $name) <option value="{{ $no }}" @selected((int) old('day_of_week') === $no)>{{ $name }}</option> @endforeach
            </select>
        </label>
        <label class="text-sm">
            <span class="block font-medium">Mulai</span>
            <input type="time" name="start_time" required value="{{ old('start_time') }}" class="{{ $input }}">
        </label>
        <label class="text-sm">
            <span class="block font-medium">Selesai</span>
            <input type="time" name="end_time" required value="{{ old('end_time') }}" class="{{ $input }}">
        </label>
        <label class="min-w-[10rem] flex-1 text-sm">
            <span class="block font-medium">Catatan</span>
            <input type="text" name="note" maxlength="100" value="{{ old('note') }}" placeholder="mis. Khusus pasien sakit" class="w-full {{ $input }}">
        </label>
        <button type="submit" class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-semibold text-white">Tambah</button>

        @foreach (['day_of_week', 'start_time', 'end_time', 'note'] as $field)
            @error($field) <p class="w-full text-xs text-red-600">{{ $message }}</p> @enderror
        @endforeach
    </form>

    <div class="mt-4 space-y-2">
        @forelse ($schedules as $schedule)
            <div class="flex items-center justify-between gap-3 rounded-lg bg-white px-4 py-3 text-sm shadow-sm">
                <div>
                    <span class="font-semibold">{{ $days[$schedule->day_of_week] }}</span>
                    &middot; {{ $schedule->timeLabel() }}
                    @if ($schedule->note) <span class="text-slate-500">&middot; {{ $schedule->note }}</span> @endif
                </div>
                <form method="POST" action="{{ route('admin.telemedicine.destroy', ['schedule' => $schedule->id]) }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="rounded-lg bg-red-100 px-3 py-1.5 text-xs font-semibold text-red-700">Hapus</button>
                </form>
            </div>
        @empty
            <div class="rounded-lg bg-white px-4 py-8 text-center text-sm text-slate-500 shadow-sm">Belum ada jadwal telemedicine.</div>
        @endforelse
    </div>
</x-layouts.admin>