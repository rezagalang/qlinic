@props(['title' => null, 'active' => 'home', 'registrationDays' => [], 'registrationHoursBefore' => 3])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? $title.' - ' : '' }}{{ config('app.name', 'Qlinic') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=poppins:300,400,500,600,700" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 font-sans text-slate-800 antialiased">
<div x-data="{ registerOpen: false, recoverOpen: {{ session('openRecover') ? 'true' : 'false' }} }"
     x-on:open-register.window="registerOpen = true"
     x-on:open-recover.window="recoverOpen = true"
     x-on:keydown.escape.window="registerOpen = false; recoverOpen = false"
     class="min-h-screen lg:flex">

    {{-- Sidebar (desktop) --}}
    <aside class="hidden lg:flex lg:w-64 lg:shrink-0 lg:flex-col bg-brand-500 text-white lg:sticky lg:top-0 lg:h-screen">
        <a href="{{ url('/') }}" class="flex items-center gap-3 border-b border-white/20 px-5 py-4">
            <span class="grid h-10 w-10 place-items-center rounded-full bg-white text-brand-600 font-bold">Q</span>
            <span class="text-xl font-light">Qlinic.id!</span>
        </a>

        <div class="m-3 rounded-lg bg-white px-3 py-2 text-sm text-slate-700">
            User Login:
            <div class="font-semibold text-slate-900">Guest</div>
        </div>

        <nav class="mt-2 flex flex-col gap-1 px-3 text-sm">
            <a href="{{ url('/') }}"
               class="rounded-lg px-3 py-2 {{ $active === 'home' ? 'bg-white/20 font-semibold' : 'hover:bg-white/10' }}">Beranda</a>
            <a href="{{ url('/antrian') }}"
               class="rounded-lg px-3 py-2 {{ $active === 'queue' ? 'bg-white/20 font-semibold' : 'hover:bg-white/10' }}">Antrian Saya</a>
            <button type="button" x-on:click="registerOpen = true"
                    class="mt-3 rounded-lg bg-white px-3 py-2 text-left font-semibold text-brand-700 hover:bg-brand-50">
                Daftar Antrian
            </button>
        </nav>

        <p class="mt-auto px-5 py-4 text-xs text-white/80">
            Copyright &copy; <strong>Qlinic.id!</strong>, {{ date('Y') }}
        </p>
    </aside>

    {{-- Konten --}}
    <div class="mx-auto w-full max-w-md lg:max-w-none lg:flex-1">
        <main class="pb-28 lg:mx-auto lg:max-w-3xl lg:pb-10">
            @if (session('status'))
                <div role="status" class="mx-3 mt-3 rounded-lg bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div role="alert" class="mx-3 mt-3 rounded-lg bg-red-50 px-4 py-3 text-sm font-medium text-red-700">{{ session('error') }}</div>
            @endif

            {{ $slot }}
        </main>
    </div>

    {{-- Bottom nav (mobile) --}}
    <nav class="fixed inset-x-0 bottom-0 z-30 mx-auto max-w-md bg-black text-xs text-white lg:hidden">
        <div class="relative grid grid-cols-2">
            <a href="{{ url('/') }}"
               class="flex h-14 flex-col items-center justify-center gap-0.5 {{ $active === 'home' ? 'bg-slate-700 font-semibold text-yellow-300' : '' }}">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M5 21V7l7-4 7 4v14M9 9h.01M12 9h.01M15 9h.01M9 13h.01M12 13h.01M15 13h.01"/></svg>
                Beranda
            </a>
            <a href="{{ url('/antrian') }}"
               class="flex h-14 flex-col items-center justify-center gap-0.5 {{ $active === 'queue' ? 'bg-slate-700 font-semibold text-yellow-300' : '' }}">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12V5a2 2 0 0 1 2-2h7l9 9-9 9-9-9zM8 8h.01"/></svg>
                Antrian Saya
            </a>
            <button type="button" x-on:click="registerOpen = true"
                    class="absolute left-1/2 -top-5 flex h-12 w-40 -translate-x-1/2 flex-col items-center justify-center rounded-xl border-2 border-white bg-brand-500 text-[11px] font-medium shadow-lg">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 4H5a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2h13a2 2 0 0 0 2-2v-6M18.5 2.5a2.1 2.1 0 0 1 3 3L12 15l-4 1 1-4z"/></svg>
                Daftar Antrian
            </button>
        </div>
    </nav>

    <x-register-modal :days="$registrationDays" :hours-before="$registrationHoursBefore" />
    <x-recover-modal />
</div>
</body>
</html>
