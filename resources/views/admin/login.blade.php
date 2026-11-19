<x-layouts.admin title="Masuk" :guest="true">
    <div class="mx-auto mt-10 w-full max-w-sm">
        <div class="mb-5 text-center">
            <span class="mx-auto grid h-14 w-14 place-items-center rounded-full bg-brand-500 text-2xl font-bold text-white">Q</span>
            <h1 class="mt-3 text-xl font-semibold">Masuk Panel Qlinic</h1>
        </div>

        <form method="POST" action="{{ route('admin.login.store') }}" class="space-y-4 rounded-lg bg-white p-5 shadow">
            @csrf

            <label class="block text-sm">
                <span class="font-medium">Email</span>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                       class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-100">
            </label>

            <label class="block text-sm">
                <span class="font-medium">Password</span>
                <input type="password" name="password" required autocomplete="current-password"
                       class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-100">
            </label>

            @error('email')
                <p class="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">{{ $message }}</p>
            @enderror

            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="remember" value="1" class="accent-brand-500"> Ingat saya
            </label>

            <button type="submit" class="w-full rounded-lg bg-brand-500 py-2.5 text-sm font-semibold text-white">Masuk</button>
        </form>
    </div>
</x-layouts.admin>