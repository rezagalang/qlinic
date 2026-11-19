@php $bag = $errors->getBag('recover'); @endphp
<div x-cloak x-show="recoverOpen" class="fixed inset-0 z-50 flex items-end justify-center bg-black/50 lg:items-center"
     role="dialog" aria-modal="true" aria-labelledby="recover-title">
    <div x-on:click.outside="recoverOpen = false"
         class="relative w-full max-w-md rounded-t-3xl bg-white p-5 shadow-xl lg:rounded-3xl">
        <button type="button" x-on:click="recoverOpen = false" aria-label="Tutup"
                class="absolute right-4 top-4 grid h-10 w-10 place-items-center rounded-full bg-red-600 text-white">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg>
        </button>

        <h2 id="recover-title" class="pr-12 text-lg font-semibold">Lihat nomor antrian saya</h2>
        <p class="mt-1 text-xs text-slate-500">Masukkan nomor HP dan tanggal lahir pasien yang Anda gunakan saat mendaftar.</p>

        <form method="POST" action="{{ route('queue.recover') }}" class="mt-4 space-y-3">
            @csrf
            <label class="block text-sm">
                <span class="font-medium">Nomor HP</span>
                <input type="tel" name="phone" inputmode="numeric" placeholder="08xxxxxxxxxx" required
                       value="{{ session('openRecover') ? old('phone') : '' }}"
                       class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-100">
                @if ($bag->has('phone'))
                    <span class="mt-1 block text-xs text-red-600">{{ $bag->first('phone') }}</span>
                @endif
            </label>
            <label class="block text-sm">
                <span class="font-medium">Tanggal lahir</span>
                <input type="date" name="birth_date" required
                       value="{{ session('openRecover') ? old('birth_date') : '' }}"
                       class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-100">
                @if ($bag->has('birth_date'))
                    <span class="mt-1 block text-xs text-red-600">{{ $bag->first('birth_date') }}</span>
                @endif
            </label>
            <button type="submit" class="w-full rounded-lg bg-brand-500 py-2.5 text-sm font-semibold text-white">Cari antrian</button>
        </form>
    </div>
</div>
