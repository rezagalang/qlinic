<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Clinic;
use App\Models\QueueTicket;
use App\Models\Service;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ServiceController extends Controller
{
    public function index(): View
    {
        return view('admin.services.index', [
            'services' => Service::query()->orderBy('sort_order')->orderBy('id')->get(),
            'icons' => Service::ICONS,
            'usedIds' => QueueTicket::query()->distinct()->pluck('service_id')->all(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $base = Str::slug($data['name']) ?: 'layanan';
        $slug = $base;
        for ($i = 2; Service::query()->where('slug', $slug)->exists(); $i++) {
            $slug = $base.'-'.$i;
        }

        Service::create($data + [
            'clinic_id' => Clinic::query()->firstOrFail()->id,
            'slug' => $slug,
            'is_active' => true,
        ]);

        return back()->with('status', 'Layanan ditambahkan.');
    }

    public function update(Request $request, Service $service): RedirectResponse
    {
        $service->update($this->validated($request) + ['is_active' => $request->boolean('is_active')]);

        return back()->with('status', 'Layanan "'.$service->name.'" disimpan.');
    }

    public function destroy(Service $service): RedirectResponse
    {
        if (QueueTicket::query()->where('service_id', $service->id)->exists()) {
            return back()->with('error', 'Layanan sudah dipakai pada antrian. Nonaktifkan saja, jangan dihapus.');
        }

        $service->delete();

        return back()->with('status', 'Layanan dihapus.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'icon' => ['required', Rule::in(array_keys(Service::ICONS))],
            'sort_order' => ['nullable', 'integer', 'between:0,999'],
        ]);

        $data['sort_order'] = $data['sort_order'] ?? 0;

        return $data;
    }
}