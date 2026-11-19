<x-layouts.app :registration-days="$registrationDays" :registration-hours-before="$registrationHoursBefore" active="home">
    {{-- Header klinik --}}
    <header class="rounded-br-[3rem] bg-brand-500 px-5 pb-16 pt-6 text-white lg:rounded-br-[4rem]">
        <p class="text-sm font-light">{{ $clinic->specialty }}</p>
        <h1 class="text-2xl font-bold leading-tight">{{ $clinic->name }}</h1>
        <p class="mt-4 flex items-center gap-2 text-sm font-semibold">
            <svg class="h-4 w-4 shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2a7 7 0 0 0-7 7c0 5 7 13 7 13s7-8 7-13a7 7 0 0 0-7-7zm0 9.5A2.5 2.5 0 1 1 12 6a2.5 2.5 0 0 1 0 5.5z"/></svg>
            {{ $clinic->address }}
        </p>
    </header>

    {{-- Kontak --}}
    <section class="-mt-12 px-4">
        <div class="rounded-xl bg-brand-50 p-4 shadow-lg">
            <h2 class="text-sm font-semibold text-brand-600">Kontak Layanan Klinik</h2>
            <a href="https://wa.me/{{ $clinic->whatsapp }}?text={{ rawurlencode('Halo, saya ingin bertanya mengenai layanan klinik.') }}" target="_blank" rel="noopener"
               class="mt-2 flex items-center gap-5">
                <span class="grid h-14 w-14 place-items-center rounded-full bg-brand-500 text-white">
                    <svg class="h-8 w-8" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2a10 10 0 0 0-8.6 15L2 22l5.1-1.3A10 10 0 1 0 12 2zm5.2 13.9c-.2.6-1.3 1.2-1.8 1.2-.5.1-1 .2-3.3-.7-2.8-1.2-4.6-4-4.7-4.2-.1-.2-1.1-1.5-1.1-2.8s.7-2 1-2.3c.2-.3.5-.3.7-.3h.5c.2 0 .4 0 .6.5l.8 2c.1.1.1.3 0 .5l-.4.6c-.1.1-.2.3-.1.5.5.9 1.2 1.6 2 2.2.9.5 1.2.5 1.4.3l.8-1c.2-.2.4-.2.6-.1l1.9.9c.2.1.4.2.5.3.1.2.1.8-.1 1.4z"/></svg>
                </span>
                <span>
                    <span class="block text-2xl font-semibold text-brand-500">{{ $clinic->whatsapp_label }}</span>
                    <span class="block text-xs text-brand-500">whatsapp chat line</span>
                </span>
            </a>
        </div>
    </section>

    {{-- Layanan --}}
    <section class="mt-5 px-4">
        <h2 class="text-sm font-semibold text-brand-600">Daftar Antrian Online</h2>
        <div class="mt-2 grid grid-cols-3 gap-2">
            @foreach ($services as $service)
                <button type="button" x-on:click="$dispatch('open-register')"
                        class="flex flex-col items-center gap-1 rounded-lg bg-brand-100 px-2 py-4 text-center text-xs hover:bg-brand-100/70">
                    <x-service-icon :name="$service->icon" class="h-8 w-8 text-slate-800" />
                    {{ $service->name }}
                </button>
            @endforeach
        </div>
    </section>

    {{-- Telemedicine --}}
    <section class="mt-5 px-4">
        <h2 class="text-sm font-semibold text-brand-600">Telemedicine</h2>
        <div class="mt-2 flex items-start gap-3 rounded-lg bg-brand-100 p-3">
            <svg class="h-14 w-14 shrink-0 text-brand-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path stroke-linecap="round" d="M4 21c0-4 3.6-7 8-7s8 3 8 7"/></svg>
            <div class="text-xs leading-snug">
                <span class="font-semibold text-red-600">Khusus pasien sakit,</span>
                Konsultasikan kesehatan buah hati anda melalui whatsapp live chat sesuai dengan ketersediaan jadwal
                <a href="{{ route('telemedicine') }}" class="text-blue-700">(Tap disini untuk melihat jadwal)</a>
                <div class="mt-2 flex items-center justify-between gap-2">
                    <span class="text-[11px] font-semibold text-slate-700">
                        @if ($telemedicineToday->isNotEmpty())
                            Hari ini: {{ $telemedicineToday->map->timeLabel()->implode(', ') }}
                        @else
                            Hari ini tidak ada jadwal
                        @endif
                    </span>
                    <a href="{{ route('telemedicine') }}" class="inline-block rounded-full bg-yellow-400 px-3 py-0.5 text-[10px] font-semibold text-black">Cek Jadwal Telemedicine</a>
                </div>
            </div>
        </div>
    </section>

    {{-- Lokasi --}}
    <section class="mt-5 px-4">
        <h2 class="text-sm font-semibold text-brand-600">Lokasi Klinik</h2>
        @if ($clinic->map_embed_url)
            <iframe src="{{ $clinic->map_embed_url }}" title="Peta lokasi klinik" loading="lazy" referrerpolicy="no-referrer-when-downgrade"
                    class="mt-2 h-56 w-full rounded-lg border border-slate-200 bg-white"></iframe>
        @else
            <div class="mt-2 grid h-56 place-items-center rounded-lg border border-slate-200 bg-white text-sm text-slate-400">Peta lokasi belum diatur</div>
        @endif
        @if ($clinic->maps_url)
            <a href="{{ $clinic->maps_url }}" target="_blank" rel="noopener"
               class="mt-2 block rounded bg-brand-500 py-2 text-center text-sm font-semibold text-white">
                Petunjuk arah menuju klinik klik disini
            </a>
        @endif
    </section>

    <footer class="mt-6 bg-black px-4 py-5 text-center text-sm text-yellow-300">
        {{ $clinic->footer_text }}
    </footer>
</x-layouts.app>