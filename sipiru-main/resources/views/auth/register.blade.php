<x-layouts.guest title="Registrasi Eksternal" :wide="true">
    <div class="card">
        <div class="border-b border-slate-100 px-6 py-5">
            <h1 class="text-lg font-semibold">Registrasi Pengguna Eksternal</h1>
            <p class="mt-1 text-sm text-slate-500">Untuk peminjam dari luar Universitas Syiah Kuala. Pegawai USK cukup login dengan akun SIMPEG.</p>
        </div>
        <form method="POST" action="{{ route('register.store') }}" enctype="multipart/form-data" class="grid grid-cols-1 gap-5 p-6 sm:grid-cols-2">
            @csrf
            <div class="sm:col-span-2">
                <label for="name" class="label">Nama lengkap (sesuai KTP)</label>
                <input id="name" name="name" value="{{ old('name') }}" required maxlength="150" class="input @error('name') input-error @enderror" autocomplete="name">
                <x-field-error name="name" />
            </div>
            <div>
                <label for="email" class="label">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required class="input @error('email') input-error @enderror" autocomplete="email">
                <x-field-error name="email" />
            </div>
            <div>
                <label for="phone" class="label">No. HP</label>
                <input id="phone" name="phone" type="tel" value="{{ old('phone') }}" required class="input @error('phone') input-error @enderror" placeholder="08xxxxxxxxxx" autocomplete="tel">
                <x-field-error name="phone" />
            </div>
            <div>
                <label for="nik" class="label">NIK</label>
                <input id="nik" name="nik" inputmode="numeric" maxlength="16" value="{{ old('nik') }}" required class="input @error('nik') input-error @enderror" placeholder="16 digit">
                <x-field-error name="nik" />
            </div>
            <div>
                <label for="ktp" class="label">Upload KTP</label>
                <input id="ktp" name="ktp" type="file" required accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                       class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-medium file:text-slate-700 hover:file:bg-slate-200">
                <p class="help">PDF/JPG/PNG, maks. 2 MB. Disimpan privat & hanya dapat dilihat admin.</p>
                <x-field-error name="ktp" />
            </div>
            <div>
                <label for="password" class="label">Password</label>
                <input id="password" name="password" type="password" required class="input @error('password') input-error @enderror" autocomplete="new-password">
                <p class="help">Min. 8 karakter, huruf besar & kecil, serta angka.</p>
                <x-field-error name="password" />
            </div>
            <div>
                <label for="password_confirmation" class="label">Konfirmasi password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required class="input" autocomplete="new-password">
            </div>
            <div class="sm:col-span-2" x-data="{ open: false }">
                <label class="flex items-start gap-2 text-sm text-slate-700">
                    <input type="checkbox" name="policy" value="1" @checked(old('policy')) class="mt-0.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    <span>Saya telah membaca dan menyetujui <button type="button" @click="open = !open" class="font-medium text-indigo-600 underline-offset-2 hover:underline">Kebijakan Peminjaman Ruang</button>.</span>
                </label>
                <x-field-error name="policy" />
                <div x-cloak x-show="open" class="mt-3 max-h-64 overflow-y-auto rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm">
                    @include('policy._content')
                </div>
            </div>
            <div class="flex flex-col-reverse items-center justify-between gap-3 sm:col-span-2 sm:flex-row">
                <a href="{{ route('login') }}#eksternal" class="text-sm text-slate-500 hover:text-slate-800">Sudah punya akun? Masuk</a>
                <button class="btn btn-primary w-full sm:w-auto">Daftar</button>
            </div>
        </form>
    </div>
</x-layouts.guest>
