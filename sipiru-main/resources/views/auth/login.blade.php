<x-layouts.guest title="Masuk">
    {{-- Tautan Navigasi Kembali ke Kalender Publik --}}
    <div class="mb-4">
        <a href="{{ route('calendar') }}" class="inline-flex items-center gap-2 text-sm font-medium text-slate-600 hover:text-slate-900 transition-colors">
            <x-icon name="arrow-left" class="size-4" />
            <span>Kembali ke Kalender</span>
        </a>
    </div>

    <div class="card" x-data="{ tab: window.location.hash === '#eksternal' || @js($errors->has('email')) ? 'eksternal' : 'pegawai' }">
        <div class="border-b border-slate-100 px-6 pt-6">
            <h1 class="text-lg font-semibold">Masuk ke akun Anda</h1>
            <p class="mt-1 text-sm text-slate-500">Pilih jenis pengguna untuk melanjutkan.</p>
            <div class="mt-5 grid grid-cols-2 gap-1 rounded-lg bg-slate-100 p-1 text-sm font-medium" role="tablist">
                <button type="button" role="tab" @click="tab = 'pegawai'" :class="tab === 'pegawai' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500'" class="rounded-md px-3 py-2 transition">Pegawai USK</button>
                <button type="button" role="tab" @click="tab = 'eksternal'" :class="tab === 'eksternal' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500'" class="rounded-md px-3 py-2 transition">Eksternal</button>
            </div>
            <div class="h-6"></div>
        </div>

        {{-- Pegawai: SIMPEG / provider internal --}}
        <form x-show="tab === 'pegawai'" method="POST" action="{{ route('login.internal') }}" class="space-y-4 p-6" id="pegawai">
            @csrf
            <div class="flex items-start gap-2 rounded-lg bg-indigo-50 px-3 py-2.5 text-xs text-indigo-800">
                <x-icon name="shield-check" class="mt-0.5 size-4" />
                <span>Masuk menggunakan akun <strong>SIMPEG USK</strong>.
                    @if ($providerName === 'local')<br><span class="text-indigo-600">Mode development: akun pegawai lokal (lihat README).</span>@endif
                </span>
            </div>
            <div>
                <label for="nip" class="label">NIP</label>
                <input id="nip" name="nip" type="text" inputmode="numeric" autocomplete="username" value="{{ old('nip') }}" required autofocus class="input @error('nip') input-error @enderror" placeholder="Nomor Induk Pegawai">
                <x-field-error name="nip" />
            </div>
            <div>
                <label for="password-pegawai" class="label">Password</label>
                <input id="password-pegawai" name="password" type="password" autocomplete="current-password" required class="input">
            </div>
            <button class="btn btn-primary w-full"><x-icon name="log-in" class="size-4" /> Masuk</button>
        </form>

        {{-- Eksternal: email + password --}}
        <form x-cloak x-show="tab === 'eksternal'" method="POST" action="{{ route('login.external') }}" class="space-y-4 p-6" id="eksternal">
            @csrf
            <div>
                <label for="email" class="label">Email</label>
                <input id="email" name="email" type="email" autocomplete="email" value="{{ old('email') }}" required class="input @error('email') input-error @enderror">
                <x-field-error name="email" />
            </div>
            <div>
                <label for="password-eksternal" class="label">Password</label>
                <input id="password-eksternal" name="password" type="password" autocomplete="current-password" required class="input">
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" name="remember" value="1" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"> Ingat saya
            </label>
            <button class="btn btn-primary w-full"><x-icon name="log-in" class="size-4" /> Masuk</button>
            <p class="text-center text-sm text-slate-500">Belum punya akun? <a href="{{ route('register') }}" class="font-medium text-indigo-600 hover:text-indigo-700">Daftar sebagai pengguna eksternal</a></p>
        </form>
    </div>
    <p class="mt-4 text-center text-xs text-slate-400"><a href="{{ route('admin.login') }}" class="hover:text-slate-600">Login Admin</a></p>
</x-layouts.guest>