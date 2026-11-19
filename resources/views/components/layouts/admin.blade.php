@props(['title' => null, 'guest' => false, 'refresh' => false])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title ? $title.' - ' : '' }}Panel {{ config('app.name', 'Qlinic') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=poppins:300,400,500,600,700" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 font-sans text-slate-800 antialiased">
@unless ($guest)
    <header class="bg-brand-500 text-white shadow">
        <div class="mx-auto flex max-w-5xl flex-wrap items-center gap-x-6 gap-y-2 px-4 py-3">
            <a href="{{ route('admin.queue') }}" class="text-lg font-semibold">Qlinic.id! <span class="text-xs font-light">Panel</span></a>

            <nav class="flex items-center gap-1 text-sm">
                <a href="{{ route('admin.queue') }}"
                   class="rounded-lg px-3 py-1.5 {{ request()->routeIs('admin.queue*') ? 'bg-white/20 font-semibold' : 'hover:bg-white/10' }}">Antrian</a>
                @if (auth()->user()?->isAdmin())
                    @foreach ([['admin.schedule', 'Jadwal'], ['admin.telemedicine', 'Telemedicine'], ['admin.services', 'Layanan'], ['admin.clinic', 'Klinik']] as [$routeName, $label])
                        <a href="{{ route($routeName) }}"
                           class="rounded-lg px-3 py-1.5 {{ request()->routeIs($routeName.'*') ? 'bg-white/20 font-semibold' : 'hover:bg-white/10' }}">{{ $label }}</a>
                    @endforeach
                @endif
            </nav>

            <div class="ml-auto flex items-center gap-3 text-xs">
                <span>{{ auth()->user()?->name }} <span class="opacity-80">({{ auth()->user()?->role }})</span></span>
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit" class="rounded-lg bg-white/20 px-3 py-1.5 font-semibold hover:bg-white/30">Keluar</button>
                </form>
            </div>
        </div>
    </header>
@endunless

<main class="mx-auto max-w-5xl px-4 py-5">
    @if (session('status'))
        <div role="status" class="mb-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div role="alert" class="mb-4 rounded-lg bg-red-50 px-4 py-3 text-sm font-medium text-red-700">{{ session('error') }}</div>
    @endif

    {{ $slot }}
</main>

@if ($refresh)
    {{-- Muat ulang otomatis agar daftar antrian selalu terbaru (tidak aktif saat tab tersembunyi). --}}
    <div x-data x-init="setInterval(() => { if (! document.hidden) location.reload() }, 20000)"></div>
@endif
</body>
</html>