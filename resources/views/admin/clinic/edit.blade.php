@php $input = 'mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-100'; @endphp
<x-layouts.admin title="Profil Klinik">
    <h1 class="text-lg font-semibold">Profil Klinik</h1>

    <form method="POST" action="{{ route('admin.clinic.update') }}" class="mt-4 space-y-4 rounded-lg bg-white p-5 shadow-sm">
        @csrf
        @method('PUT')

        <div class="grid gap-4 sm:grid-cols-2">
            <label class="block text-sm">
                <span class="font-medium">Nama dokter / klinik</span>
                <input type="text" name="name" value="{{ old('name', $clinic->name) }}" required maxlength="150" class="{{ $input }}">
                @error('name') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
            </label>
            <label class="block text-sm">
                <span class="font-medium">Jenis klinik</span>
                <input type="text" name="specialty" value="{{ old('specialty', $clinic->specialty) }}" maxlength="100" placeholder="mis. Klinik Anak" class="{{ $input }}">
                @error('specialty') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
            </label>
        </div>

        <label class="block text-sm">
            <span class="font-medium">Alamat</span>
            <input type="text" name="address" value="{{ old('address', $clinic->address) }}" maxlength="255" class="{{ $input }}">
            @error('address') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
        </label>

        <label class="block text-sm">
            <span class="font-medium">Nomor WhatsApp</span>
            <input type="tel" name="whatsapp" value="{{ old('whatsapp', $clinic->whatsapp_label) }}" inputmode="numeric" placeholder="0852 3911 6000" class="{{ $input }}">
            <span class="text-xs text-slate-500">Dipakai untuk tombol chat dan telemedicine. Format 08xx atau +62.</span>
            @error('whatsapp') <span class="block text-xs text-red-600">{{ $message }}</span> @enderror
        </label>

        <label class="block text-sm">
            <span class="font-medium">Link petunjuk arah (Google Maps)</span>
            <input type="url" name="maps_url" value="{{ old('maps_url', $clinic->maps_url) }}" placeholder="https://maps.app.goo.gl/..." class="{{ $input }}">
            @error('maps_url') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
        </label>

        <label class="block text-sm">
            <span class="font-medium">Peta tersemat</span>
            <textarea name="map_embed_url" rows="3" placeholder="Tempel link atau kode iframe dari Google Maps &gt; Bagikan &gt; Sematkan peta" class="{{ $input }}">{{ old('map_embed_url', $clinic->map_embed_url) }}</textarea>
            @error('map_embed_url') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
        </label>

        <label class="block text-sm">
            <span class="font-medium">Teks footer</span>
            <input type="text" name="footer_text" value="{{ old('footer_text', $clinic->footer_text) }}" maxlength="255" class="{{ $input }}">
            @error('footer_text') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
        </label>

        <div class="grid gap-4 sm:grid-cols-2">
            <label class="block text-sm">
                <span class="font-medium">Pendaftaran online dibuka (jam sebelum praktik)</span>
                <input type="number" name="registration_hours_before" min="0" max="12" required
                       value="{{ old('registration_hours_before', $clinic->registration_hours_before) }}" class="{{ $input }}">
                @error('registration_hours_before') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
            </label>
            <label class="block text-sm">
                <span class="font-medium">Jumlah hari yang bisa dipilih</span>
                <input type="number" name="booking_days_ahead" min="1" max="14" required
                       value="{{ old('booking_days_ahead', $clinic->booking_days_ahead) }}" class="{{ $input }}">
                @error('booking_days_ahead') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
            </label>
        </div>

        <button type="submit" class="rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-semibold text-white">Simpan</button>
    </form>
</x-layouts.admin>