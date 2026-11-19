<x-layouts.app title="Telemedicine" :registration-days="$registrationDays" :registration-hours-before="$registrationHoursBefore" active="home">
    <header class="flex items-center gap-3 bg-brand-500 px-4 py-4 text-white shadow-md">
        <a href="{{ route('home') }}" aria-label="Kembali">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 5l-7 7 7 7"/></svg>
        </a>
        <h1 class="ml-auto text-sm font-semibold">Jadwal Telemedicine</h1>
    </header>

    <div class="space-y-4 px-3 py-4">
        <div class="rounded-lg bg-brand-100 p-4 text-sm">
            <p><span class="font-semibold text-red-600">Khusus pasien sakit.</span>
                Konsultasikan kesehatan buah hati Anda melalui WhatsApp live chat sesuai jadwal di bawah.</p>

            <p class="mt-3 text-xs font-semibold {{ $openNow ? 'text-emerald-700' : 'text-slate-600' }}">
                {{ $openNow ? 'Sedang buka sekarang' : 'Di luar jam telemedicine' }}
            </p>

            @if ($whatsappUrl)
                <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener"
                   class="mt-3 block rounded-lg bg-brand-500 py-2.5 text-center text-sm font-semibold text-white">
                    Chat WhatsApp {{ $clinic->whatsapp_label }}
                </a>
            @endif
        </div>

        <div class="space-y-2">
            @foreach ($days as $no => $name)
                @php $rows = $byDay->get($no, collect()); @endphp
                <div class="flex items-start justify-between gap-3 rounded-lg bg-white px-4 py-3 shadow-sm {{ $no === $today ? 'ring-2 ring-brand-500' : '' }}">
                    <div class="text-sm font-semibold">
                        {{ $name }}
                        @if ($no === $today) <span class="ml-1 rounded-full bg-brand-50 px-2 py-0.5 text-[10px] font-semibold text-brand-700">Hari ini</span> @endif
                    </div>
                    <div class="text-right text-sm">
                        @forelse ($rows as $row)
                            <div>{{ $row->timeLabel() }}@if ($row->note) <span class="block text-[11px] text-slate-500">{{ $row->note }}</span> @endif</div>
                        @empty
                            <span class="text-xs text-slate-400">Tidak ada jadwal</span>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-layouts.app>