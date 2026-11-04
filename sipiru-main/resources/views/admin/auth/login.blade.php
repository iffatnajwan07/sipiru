<x-layouts.guest title="Login Admin">
    <div class="card">
        <form method="POST" action="{{ route('admin.login.store') }}" class="space-y-4 p-6">
            @csrf
            <div>
                <div class="mb-3 inline-flex items-center gap-1.5 rounded-full bg-slate-900 px-2.5 py-1 text-xs font-medium text-white"><x-icon name="key-round" class="size-3.5" /> Administrator</div>
                <h1 class="text-lg font-semibold">Login Admin</h1>
                <p class="mt-1 text-sm text-slate-500">Khusus pengelola sistem peminjaman ruang.</p>
            </div>
            <div>
                <label for="login" class="label">Email atau NIP</label>
                <input id="login" name="login" type="text" autocomplete="username" value="{{ old('login') }}" required autofocus class="input @error('login') input-error @enderror">
                <x-field-error name="login" />
            </div>
            <div>
                <label for="password" class="label">Password</label>
                <input id="password" name="password" type="password" autocomplete="current-password" required class="input">
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" name="remember" value="1" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"> Ingat saya
            </label>
            <button class="btn btn-primary w-full"><x-icon name="log-in" class="size-4" /> Masuk</button>
        </form>
    </div>
    <p class="mt-4 text-center text-xs text-slate-400"><a href="{{ route('login') }}" class="hover:text-slate-600">← Login pengguna</a></p>
</x-layouts.guest>
