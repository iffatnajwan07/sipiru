<x-layouts.app title="Profile">
    <x-page-header title="Profile" subtitle="Informasi akun Anda." />
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="card">
            <div class="card-header"><h2 class="card-title">Data Akun</h2><x-badge tone="indigo">{{ $user->user_type->label() }}</x-badge></div>
            <form method="POST" action="{{ route('profile.update') }}" class="card-body space-y-4">
                @csrf @method('PUT')
                <div>
                    <label for="name" class="label">Nama</label>
                    <input id="name" name="name" value="{{ old('name', $user->name) }}" class="input" @disabled(! $user->isEksternal())>
                    @unless ($user->isEksternal())<p class="help">Nama & email pegawai mengikuti data SIMPEG.</p>@endunless
                    <x-field-error name="name" />
                </div>
                <div>
                    <label class="label">Email</label>
                    <input value="{{ $user->email }}" class="input" disabled>
                </div>
                <div>
                    <label class="label">{{ $user->identifierLabel() }}</label>
                    <input value="{{ $user->identifier() }}" class="input" disabled>
                </div>
                @if ($user->position)
                    <div><label class="label">Jabatan</label><input value="{{ $user->position }}" class="input" disabled></div>
                @endif
                <div>
                    <label for="phone" class="label">No. HP</label>
                    <input id="phone" name="phone" value="{{ old('phone', $user->phone) }}" class="input @error('phone') input-error @enderror">
                    <x-field-error name="phone" />
                </div>
                @if ($user->ktp_path)
                    <div class="flex items-center justify-between rounded-lg border border-slate-200 px-3 py-2.5 text-sm">
                        <span class="flex items-center gap-2"><x-icon name="id-card" class="size-4 text-slate-500" /> File KTP</span>
                        <a href="{{ route('profile.ktp') }}" target="_blank" class="font-medium text-indigo-600 hover:underline">Lihat</a>
                    </div>
                @endif
                <div class="text-xs text-slate-500">Kebijakan disetujui: {{ $user->policy_accepted_at?->translatedFormat('d F Y H:i') ?? '-' }} (versi {{ $user->policy_version ?? '-' }})</div>
                <button class="btn btn-primary">Simpan</button>
            </form>
        </div>

        @if ($user->isEksternal())
            <div class="card self-start">
                <div class="card-header"><h2 class="card-title">Ubah Password</h2></div>
                <form method="POST" action="{{ route('profile.password') }}" class="card-body space-y-4">
                    @csrf @method('PUT')
                    <div>
                        <label for="current_password" class="label">Password saat ini</label>
                        <input id="current_password" name="current_password" type="password" class="input" autocomplete="current-password">
                        <x-field-error name="current_password" />
                    </div>
                    <div>
                        <label for="password" class="label">Password baru</label>
                        <input id="password" name="password" type="password" class="input" autocomplete="new-password">
                        <x-field-error name="password" />
                    </div>
                    <div>
                        <label for="password_confirmation" class="label">Konfirmasi password baru</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" class="input" autocomplete="new-password">
                    </div>
                    <button class="btn btn-secondary"><x-icon name="lock" class="size-4" /> Ubah Password</button>
                </form>
            </div>
        @else
            <div class="card card-body self-start text-sm text-slate-600">
                <div class="flex items-start gap-3"><x-icon name="shield-check" class="size-5 text-indigo-600" /><p>Password akun pegawai dikelola oleh <strong>SIMPEG USK</strong> dan tidak disimpan di sistem ini.</p></div>
            </div>
        @endif
    </div>
</x-layouts.app>
