@props(['days' => [], 'hoursBefore' => 3])
@php
    $statusMap = [
        'open'    => ['label' => 'Tersedia',                       'class' => 'text-emerald-600'],
        'full'    => ['label' => 'Kuota Antrian Penuh',            'class' => 'text-red-600'],
        'closed'  => ['label' => 'Pendaftaran Online Tutup',       'class' => 'text-red-600'],
        'not_yet' => ['label' => 'Pendaftaran Online Belum dibuka', 'class' => 'text-red-600'],
        'off'     => ['label' => 'Tidak Praktik',                  'class' => 'text-red-600'],
    ];
@endphp

<div x-cloak x-show="registerOpen" class="fixed inset-0 z-50 flex items-end justify-center bg-black/50 lg:items-center"
     x-data="{ day: 0 }" role="dialog" aria-modal="true" aria-labelledby="register-title">
    <div x-on:click.outside="registerOpen = false"
         x-transition
         class="relative max-h-[90vh] w-full max-w-md overflow-y-auto rounded-t-3xl bg-white p-5 shadow-xl lg:rounded-3xl">
        <button type="button" x-on:click="registerOpen = false" aria-label="Tutup"
                class="absolute right-4 top-4 grid h-10 w-10 place-items-center rounded-full bg-red-600 text-white">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg>
        </button>

        <h2 id="register-title" class="pr-12 text-lg font-semibold">Daftar Antrian Online</h2>

        {{-- Pilih tanggal --}}
        <div class="mt-4 grid grid-cols-4 gap-2">
            @foreach ($days as $i => $d)
                <button type="button" x-on:click="day = {{ $i }}"
                        x-bind:class="day === {{ $i }} ? 'bg-brand-500 text-white' : 'bg-brand-100 text-slate-700'"
                        class="rounded-xl px-1 py-2 text-center">
                    <div class="text-xs">{{ $d['weekday'] }}</div>
                    <div class="text-2xl font-bold leading-tight">{{ $d['day'] }}</div>
                    <div class="text-[10px]">{{ $d['month'] }}</div>
                </button>
            @endforeach
        </div>

        {{-- Sesi per tanggal --}}
        @foreach ($days as $i => $d)
            <div x-show="day === {{ $i }}" class="mt-4 space-y-3">
                @foreach ($d['sessions'] as $s)
                    @php $st = $statusMap[$s['status']] ?? $statusMap['off']; @endphp
                    <div class="flex items-center justify-between gap-3 rounded-lg border border-slate-200 px-3 py-3 shadow-sm">
                        <div>
                            <div class="text-sm font-semibold">{{ $s['name'] }}</div>
                            @if ($s['time'])
                                <div class="mt-1 text-xs text-slate-500">{{ $s['time'] }}</div>
                            @endif
                        </div>
                        @if ($s['status'] === 'open')
                            <a href="{{ route('queue.register', ['date' => $d['date'], 'session' => $s['key']]) }}"
                               class="rounded-lg bg-brand-500 px-4 py-1.5 text-xs font-semibold text-white">Daftar</a>
                        @else
                            <span class="max-w-[9rem] text-center text-[11px] font-semibold leading-tight {{ $st['class'] }}">{{ $st['label'] }}</span>
                        @endif
                    </div>
                @endforeach
            </div>
        @endforeach

        <p class="mt-5 text-sm font-semibold text-red-600">
            Note: Pendaftaran secara online dibuka {{ $hoursBefore }} jam sebelum jam praktik dimulai
        </p>
    </div>
</div>
