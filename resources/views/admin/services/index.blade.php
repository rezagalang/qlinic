@php $input = 'rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-100'; @endphp
<x-layouts.admin title="Layanan">
    <h1 class="text-lg font-semibold">Layanan</h1>
    <p class="mt-1 text-sm text-slate-500">Layanan yang bisa dipilih pasien saat mendaftar. Layanan yang sudah dipakai antrian tidak bisa dihapus, nonaktifkan saja.</p>

    <form method="POST" action="{{ route('admin.services.store') }}" class="mt-4 flex flex-wrap items-end gap-3 rounded-lg bg-white p-4 shadow-sm">
        @csrf
        <label class="min-w-[12rem] flex-1 text-sm">
            <span class="block font-medium">Layanan baru</span>
            <input type="text" name="name" maxlength="100" required value="{{ old('name') }}" placeholder="mis. Tumbuh Kembang" class="mt-1 w-full {{ $input }}">
            @error('name') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
        </label>
        <label class="text-sm">
            <span class="block font-medium">Ikon</span>
            <select name="icon" class="mt-1 {{ $input }}">
                @foreach ($icons as $key => $label) <option value="{{ $key }}">{{ $label }}</option> @endforeach
            </select>
        </label>
        <label class="text-sm">
            <span class="block font-medium">Urutan</span>
            <input type="number" name="sort_order" min="0" max="999" value="{{ old('sort_order', 0) }}" class="mt-1 w-20 {{ $input }}">
        </label>
        <button type="submit" class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-semibold text-white">Tambah</button>
    </form>

    <div class="mt-4 space-y-2">
        @foreach ($services as $service)
            <div class="flex flex-wrap items-end gap-3 rounded-lg bg-white p-4 shadow-sm {{ $service->is_active ? '' : 'opacity-60' }}">
                <form id="service-{{ $service->id }}" method="POST" action="{{ route('admin.services.update', ['service' => $service->id]) }}" class="flex flex-1 flex-wrap items-end gap-3">
                    @csrf
                    @method('PUT')
                    <label class="min-w-[12rem] flex-1 text-sm">
                        <span class="block text-xs font-medium">Nama</span>
                        <input type="text" name="name" maxlength="100" required value="{{ $service->name }}" class="mt-1 w-full {{ $input }}">
                    </label>
                    <label class="text-sm">
                        <span class="block text-xs font-medium">Ikon</span>
                        <select name="icon" class="mt-1 {{ $input }}">
                            @foreach ($icons as $key => $label) <option value="{{ $key }}" @selected($service->icon === $key)>{{ $label }}</option> @endforeach
                        </select>
                    </label>
                    <label class="text-sm">
                        <span class="block text-xs font-medium">Urutan</span>
                        <input type="number" name="sort_order" min="0" max="999" value="{{ $service->sort_order }}" class="mt-1 w-20 {{ $input }}">
                    </label>
                    <label class="flex items-center gap-2 pb-2 text-sm">
                        <input type="checkbox" name="is_active" value="1" class="accent-brand-500" @checked($service->is_active)> Aktif
                    </label>
                    <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-xs font-semibold text-white">Simpan</button>
                </form>

                @unless (in_array($service->id, $usedIds, true))
                    <form method="POST" action="{{ route('admin.services.destroy', ['service' => $service->id]) }}"
                          onsubmit="return confirm('Hapus layanan ini?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="rounded-lg bg-red-100 px-3 py-2 text-xs font-semibold text-red-700">Hapus</button>
                    </form>
                @endunless
            </div>
        @endforeach
    </div>
</x-layouts.admin>