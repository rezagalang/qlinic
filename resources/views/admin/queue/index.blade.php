@php
    $badge = [
        'menunggu'   => 'bg-brand-50 text-brand-700',
        'dipanggil'  => 'bg-emerald-100 text-emerald-800',
        'selesai'    => 'bg-slate-100 text-slate-600',
        'dilewati'   => 'bg-amber-100 text-amber-800',
        'dibatalkan' => 'bg-red-100 text-red-700',
    ];
    $actionMap = [
        'menunggu'  => [['call', 'Panggil', 'bg-brand-500 text-white'], ['skip', 'Lewati', 'bg-amber-100 text-amber-800'], ['cancel', 'Batalkan', 'bg-red-100 text-red-700']],
        'dipanggil' => [['finish', 'Selesai', 'bg-emerald-600 text-white'], ['skip', 'Lewati', 'bg-amber-100 text-amber-800']],
        'dilewati'  => [['call', 'Panggil', 'bg-brand-500 text-white'], ['restore', 'Kembalikan', 'bg-slate-100 text-slate-700'], ['cancel', 'Batalkan', 'bg-red-100 text-red-700']],
    ];
@endphp

<x-layouts.admin title="Antrian" :refresh="true">
    <h1 class="text-lg font-semibold">Antrian {{ $dateLabel }} &middot; Sesi {{ $session->label() }}</h1>

    {{-- Filter --}}
    <form method="GET" action="{{ route('admin.queue') }}" class="mt-3 flex flex-wrap items-end gap-3 rounded-lg bg-white p-4 shadow">
        <label class="text-sm">
            <span class="block font-medium">Tanggal</span>
            <input type="date" name="date" value="{{ $date }}"
                   class="mt-1 rounded-lg border border-slate-300 px-3 py-2">
        </label>
        <label class="text-sm">
            <span class="block font-medium">Sesi</span>
            <select name="session" class="mt-1 rounded-lg border border-slate-300 px-3 py-2">
                @foreach (\App\Enums\PracticeSession::cases() as $s)
                    <option value="{{ $s->value }}" @selected($s === $session)>{{ $s->label() }}</option>
                @endforeach
            </select>
        </label>
        <button type="submit" class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-semibold text-white">Tampilkan</button>
    </form>

    {{-- Ringkasan --}}
    <div class="mt-4 grid grid-cols-2 gap-2 text-center text-xs sm:grid-cols-5">
        @foreach (\App\Enums\QueueStatus::cases() as $st)
            <div class="rounded-lg bg-white px-2 py-3 shadow-sm">
                <div class="text-2xl font-bold">{{ $counts[$st->value] }}</div>
                <div class="text-slate-500">{{ $st->label() }}</div>
            </div>
        @endforeach
    </div>

    {{-- Sedang dipanggil + panggil berikutnya --}}
    <div class="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-lg bg-brand-100 p-4">
        <div>
            <div class="text-xs text-slate-600">Sedang dipanggil</div>
            @if ($current)
                <div class="text-4xl font-bold text-brand-700">{{ $current->number }}</div>
                <div class="text-sm font-medium">{{ $current->patient_name }}</div>
            @else
                <div class="text-2xl font-semibold text-slate-500">-</div>
            @endif
        </div>

        <form method="POST" action="{{ route('admin.queue.next') }}">
            @csrf
            <input type="hidden" name="date" value="{{ $date }}">
            <input type="hidden" name="session" value="{{ $session->value }}">
            <button type="submit" class="rounded-lg bg-brand-500 px-5 py-3 text-sm font-semibold text-white shadow">Panggil berikutnya</button>
        </form>
    </div>

    {{-- Daftar --}}
    <div class="mt-4 space-y-2">
        @forelse ($tickets as $ticket)
            @php $actions = $actionMap[$ticket->status->value] ?? []; @endphp
            <div class="flex flex-wrap items-center gap-3 rounded-lg bg-white p-3 shadow-sm {{ $ticket->status === \App\Enums\QueueStatus::Dipanggil ? 'ring-2 ring-emerald-400' : '' }}">
                <div class="grid h-12 w-12 shrink-0 place-items-center rounded-lg bg-brand-50 text-xl font-bold text-brand-700">{{ $ticket->number }}</div>

                <div class="min-w-0 flex-1 text-sm">
                    <div class="font-semibold">{{ $ticket->patient_name }}</div>
                    <div class="text-xs text-slate-500">
                        {{ $ticket->service->name }} &middot; lahir {{ $ticket->birth_date->format('d-m-Y') }} &middot;
                        <a class="text-brand-600 underline" href="https://wa.me/62{{ substr($ticket->phone, 1) }}" target="_blank" rel="noopener">{{ $ticket->phone }}</a>
                    </div>
                    @if ($ticket->gender || $ticket->address)
                        <div class="text-xs text-slate-500">
                            {{ $ticket->gender === 'L' ? 'Laki-laki' : ($ticket->gender === 'P' ? 'Perempuan' : '') }}@if ($ticket->address) &middot; {{ $ticket->address }}@endif
                        </div>
                    @endif
                    @if ($ticket->complaint)
                        <div class="mt-0.5 text-xs italic text-slate-600">Keluhan: {{ $ticket->complaint }}</div>
                    @endif
                </div>

                <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $badge[$ticket->status->value] }}">{{ $ticket->status->label() }}</span>

                <div class="flex flex-wrap gap-1">
                    @foreach ($actions as [$action, $label, $class])
                        <form method="POST" action="{{ route('admin.queue.action', ['ticket' => $ticket->id, 'action' => $action]) }}">
                            @csrf
                            <button type="submit" class="rounded-lg px-3 py-1.5 text-xs font-semibold {{ $class }}"
                                    @if ($action === 'cancel') onclick="return confirm('Batalkan nomor {{ $ticket->number }}?')" @endif>{{ $label }}</button>
                        </form>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="rounded-lg bg-white px-4 py-8 text-center text-sm text-slate-500 shadow-sm">Belum ada antrian pada sesi ini.</div>
        @endforelse
    </div>
</x-layouts.admin>