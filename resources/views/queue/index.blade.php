<x-layouts.app title="Antrian Saya" :registration-days="$registrationDays" :registration-hours-before="$registrationHoursBefore" active="queue">
    <header class="flex items-center gap-3 bg-brand-500 px-4 py-4 text-white shadow-md">
        <a href="{{ url('/') }}" aria-label="Kembali">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 5l-7 7 7 7"/></svg>
        </a>
        <h1 class="ml-auto text-sm font-semibold">Antrian Saya</h1>
    </header>

    <div class="space-y-3 px-3 py-4"
         x-data="{
             live: {},
             async refresh() {
                 try {
                     const r = await fetch(@js(route('queue.status')), { headers: { 'Accept': 'application/json' } });
                     if (r.ok) { const d = await r.json(); this.live = Object.fromEntries(d.tickets.map(t => [t.id, t])); }
                 } catch (e) {}
             }
         }"
         x-init="@if ($tickets->isNotEmpty()) setInterval(() => { if (! document.hidden) refresh() }, 15000) @endif">
        @forelse ($tickets as $ticket)
            @php
                $current = $ticket->currentNumber();
                $ahead = $ticket->aheadCount();
            @endphp
            <div class="rounded-lg bg-white p-4 shadow">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <div class="text-xs text-slate-500">{{ $ticket->practice_date->locale('id')->translatedFormat('l, j F Y') }} &middot; Sesi {{ $ticket->session->label() }}</div>
                        <div class="mt-1 text-sm font-semibold">{{ $ticket->patient_name }}</div>
                        <div class="text-xs text-slate-500">{{ $ticket->service->name }}</div>
                    </div>
                    <div class="text-right">
                        <div class="text-xs text-slate-500">Nomor antrian</div>
                        <div class="text-4xl font-bold leading-none text-brand-600">{{ $ticket->number }}</div>
                    </div>
                </div>
                <div class="mt-3 flex items-center justify-between border-t border-slate-100 pt-3 text-xs">
                    <span class="rounded-full bg-brand-50 px-3 py-1 font-semibold text-brand-700"
                          x-text="live[@js($ticket->id)]?.status_label ?? @js($ticket->status->label())">{{ $ticket->status->label() }}</span>
                    <span class="text-slate-600">
                        Sedang dipanggil: <strong x-text="live[@js($ticket->id)]?.current ?? @js($current ?? '-')">{{ $current ?? '-' }}</strong>
                        <span x-show="(live[@js($ticket->id)]?.status ?? @js($ticket->status->value)) === 'menunggu'"
                              @if ($ticket->status !== \App\Enums\QueueStatus::Menunggu) x-cloak @endif>
                            &middot; Di depan Anda: <strong x-text="live[@js($ticket->id)]?.ahead ?? @js($ahead)">{{ $ahead }}</strong>
                        </span>
                    </span>
                </div>
            </div>
        @empty
            <div class="rounded-lg bg-white px-4 py-3 text-center text-sm font-semibold text-red-600 shadow">
                <svg class="mr-1 inline h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2 1 21h22L12 2zm1 15h-2v-2h2v2zm0-4h-2V9h2v4z"/></svg>
                Anda belum memiliki nomor antrian
            </div>
        @endforelse

        <button type="button" x-on:click="$dispatch('open-recover')"
                class="flex w-full items-start gap-3 rounded-lg bg-brand-100 p-3 text-left shadow-sm">
            <svg class="h-12 w-12 shrink-0 text-brand-500" fill="currentColor" viewBox="0 0 24 24"><path d="M3 12V5a2 2 0 0 1 2-2h7l9 9-9 9-9-9zm5-4a1.5 1.5 0 1 0 0 .01z"/></svg>
            <span>
                <span class="block text-sm font-bold text-red-600">Lihat nomor antrian saya</span>
                <span class="mt-0.5 block text-[11px] leading-snug text-slate-700">
                    Nomor antrian anda tidak muncul? atau anda mengganti smartphone/handphone anda?
                    silahkan klik disini untuk melihat no antrian anda yang sudah terdaftar sebelumnya...
                </span>
            </span>
        </button>
    </div>
</x-layouts.app>