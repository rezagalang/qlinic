<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Clinic;
use App\Support\Phone;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ClinicController extends Controller
{
    private const MAP_HOSTS = ['www.google.com', 'google.com', 'maps.google.com'];

    public function edit(): View
    {
        return view('admin.clinic.edit', ['clinic' => Clinic::query()->firstOrFail()]);
    }

    public function update(Request $request): RedirectResponse
    {
        // Admin boleh menempel kode <iframe> dari Google Maps; ambil atribut src-nya saja.
        $embed = (string) $request->input('map_embed_url');
        if (preg_match('/<iframe[^>]*\ssrc=["\']([^"\']+)["\']/i', $embed, $m)) {
            $request->merge(['map_embed_url' => html_entity_decode($m[1])]);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'specialty' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'whatsapp' => ['nullable', 'string', 'max:20'],
            'maps_url' => ['nullable', 'url:https', 'max:2000'],
            'map_embed_url' => ['nullable', 'url:https', 'max:2000', function (string $attribute, mixed $value, \Closure $fail) {
                $parts = parse_url((string) $value);

                if (! in_array($parts['host'] ?? '', self::MAP_HOSTS, true) || ! str_starts_with($parts['path'] ?? '', '/maps/embed')) {
                    $fail('Gunakan link atau kode "Sematkan peta" dari Google Maps.');
                }
            }],
            'footer_text' => ['nullable', 'string', 'max:255'],
            'registration_hours_before' => ['required', 'integer', 'between:0,12'],
            'booking_days_ahead' => ['required', 'integer', 'between:1,14'],
        ]);

        $local = Phone::normalize((string) ($data['whatsapp'] ?? ''));

        if ($local !== '' && ! Phone::isValid($local)) {
            throw ValidationException::withMessages(['whatsapp' => 'Nomor WhatsApp tidak valid. Contoh: 085239116000.']);
        }

        // Disimpan format internasional (untuk wa.me) dan label tampilan (0852 3911 6000).
        $data['whatsapp'] = $local === '' ? null : '62'.substr($local, 1);
        $data['whatsapp_label'] = $local === '' ? null : implode(' ', str_split($local, 4));

        Clinic::query()->firstOrFail()->update($data);

        return back()->with('status', 'Profil klinik disimpan.');
    }
}