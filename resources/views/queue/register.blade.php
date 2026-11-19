@php
    $months = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
               7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];

    [$oy, $om, $od] = array_pad(explode('-', (string) old('birth_date')), 3, '');
    $oy = $oy === '' ? '' : (string) (int) $oy;
    $om = $om === '' ? '' : (string) (int) $om;
    $od = $od === '' ? '' : (string) (int) $od;

    $field = 'mt-1 w-full border-0 border-b border-slate-300 bg-transparent px-1 py-2 text-sm placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-0';
    $toggle = 'flex cursor-pointer items-center justify-center gap-2 rounded-lg bg-slate-300 px-3 py-3 text-sm text-slate-700 peer-checked:bg-brand-500 peer-checked:font-semibold peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-brand-500';
@endphp

<x-layouts.app title="Daftar Antrian" :registration-days="$registrationDays" :registration-hours-before="$registrationHoursBefore" active="queue">
    <header class="bg-brand-500 px-5 py-4 text-white shadow-md">
        <p class="text-xs font-light">{{ $clinic->specialty }}</p>
        <h1 class="text-lg font-bold leading-tight">{{ $clinic->name }}</h1>
    </header>

    <div class="px-4 py-5">
        <h2 class="text-lg font-semibold">Daftar Antrian Online</h2>

        <p class="mt-3 flex items-center gap-2 text-sm font-semibold text-red-600">
            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><path stroke-linecap="round" d="M16 2v4M8 2v4M3 10h18"/></svg>
            {{ $dateLabel }} ({{ $session->label() }})@if ($time) {{ str_replace(' s/d ', '-', $time) }}@endif
        </p>

        <form method="POST" action="{{ route('queue.store') }}" class="mt-4 space-y-5">
            @csrf
            <input type="hidden" name="date" value="{{ $date }}">
            <input type="hidden" name="session" value="{{ $session->value }}">

            @error('session') <p class="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">{{ $message }}</p> @enderror

            {{-- No. HP --}}
            <label class="block text-sm font-semibold">
                No. Handphone (whatsapp)
                <input type="tel" name="phone" value="{{ old('phone') }}" inputmode="numeric" placeholder="Input no handphone / whatsapp..." required
                       class="{{ $field }} font-normal">
                @error('phone') <span class="mt-1 block text-xs font-normal text-red-600">{{ $message }}</span> @enderror
            </label>

            {{-- Nama --}}
            <label class="block text-sm font-semibold">
                Nama Pasien
                <input type="text" name="patient_name" value="{{ old('patient_name') }}" maxlength="100" autocomplete="off" placeholder="Input nama pasien..." required
                       class="{{ $field }} font-normal">
                @error('patient_name') <span class="mt-1 block text-xs font-normal text-red-600">{{ $message }}</span> @enderror
            </label>

            {{-- Jenis kelamin --}}
            <fieldset>
                <legend class="sr-only">Jenis kelamin</legend>
                <div class="grid grid-cols-2 gap-3">
                    @foreach (['L' => 'laki-laki', 'P' => 'Perempuan'] as $value => $label)
                        <label class="block">
                            <input type="radio" name="gender" value="{{ $value }}" class="peer sr-only" required @checked(old('gender') === $value)>
                            <span class="{{ $toggle }}">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path stroke-linecap="round" d="M4 21c0-4 3.6-7 8-7s8 3 8 7"/></svg>
                                {{ $label }}
                            </span>
                        </label>
                    @endforeach
                </div>
                @error('gender') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </fieldset>

            {{-- Tanggal lahir --}}
            <fieldset x-data="{
                    y: @js($oy), m: @js($om), d: @js($od),
                    get value() { return (this.y && this.m && this.d) ? this.y + '-' + String(this.m).padStart(2, '0') + '-' + String(this.d).padStart(2, '0') : ''; }
                 }">
                <legend class="text-sm font-semibold">Tanggal lahir</legend>
                <input type="hidden" name="birth_date" x-bind:value="value" value="{{ old('birth_date') }}">
                <div class="mt-1 grid grid-cols-[1fr_2fr_1.4fr] gap-2">
                    <select x-model="d" aria-label="Tanggal" class="{{ $field }}" required>
                        <option value="">Tgl</option>
                        @for ($i = 1; $i <= 31; $i++) <option value="{{ $i }}">{{ $i }}</option> @endfor
                    </select>
                    <select x-model="m" aria-label="Bulan" class="{{ $field }}" required>
                        <option value="">Bulan</option>
                        @foreach ($months as $no => $name) <option value="{{ $no }}">{{ $name }}</option> @endforeach
                    </select>
                    <select x-model="y" aria-label="Tahun" class="{{ $field }}" required>
                        <option value="">Tahun</option>
                        @for ($i = (int) now()->format('Y'); $i >= (int) now()->format('Y') - 100; $i--) <option value="{{ $i }}">{{ $i }}</option> @endfor
                    </select>
                </div>
                @error('birth_date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </fieldset>

            {{-- Alamat --}}
            <label class="block text-sm font-semibold">
                Alamat Pasien
                <input type="text" name="address" value="{{ old('address') }}" maxlength="255" placeholder="Input tempat tinggal pasien..." required
                       class="{{ $field }} font-normal">
                @error('address') <span class="mt-1 block text-xs font-normal text-red-600">{{ $message }}</span> @enderror
            </label>

            {{-- Layanan --}}
            <fieldset>
                <legend class="sr-only">Layanan</legend>
                <div class="grid grid-cols-3 gap-2">
                    @foreach ($services as $service)
                        <label class="block">
                            <input type="radio" name="service_id" value="{{ $service->id }}" class="peer sr-only" required
                                   @checked((int) old('service_id', $services->first()->id) === $service->id)>
                            <span class="{{ $toggle }} !px-2 text-center text-xs leading-tight">
                                <x-service-icon :name="$service->icon" class="h-4 w-4 shrink-0" />
                                {{ $service->name }}
                            </span>
                        </label>
                    @endforeach
                </div>
                @error('service_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </fieldset>

            {{-- Keluhan --}}
            <label class="block text-sm font-semibold">
                Keluhan/Tujuan Periksa
                <textarea name="complaint" rows="3" maxlength="500" placeholder="Tambahkan catatan keluhan atau tujuan pemeriksaan..."
                          class="{{ $field }} border border-slate-300 font-normal">{{ old('complaint') }}</textarea>
                @error('complaint') <span class="mt-1 block text-xs font-normal text-red-600">{{ $message }}</span> @enderror
            </label>

            <div class="grid grid-cols-2 gap-3 pt-1">
                <a href="{{ route('home') }}" class="rounded-lg bg-red-600 py-3 text-center text-sm font-semibold text-white">Batal</a>
                <button type="submit" class="rounded-lg bg-brand-500 py-3 text-sm font-semibold text-white">Daftar</button>
            </div>
        </form>
    </div>
</x-layouts.app>