<x-layouts.app title="Settings · Email">
    <x-page-header title="Settings · Email" subtitle="Konfigurasi SMTP untuk notifikasi email." />

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <form method="POST" action="{{ route('admin.settings.email.update') }}" class="card lg:col-span-2" x-data="{ source: @js(old('source', $values['source'])) }">
            @csrf @method('PUT')
            <div class="card-body space-y-5">
                <div>
                    <span class="label">Sumber konfigurasi</span>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <label class="flex cursor-pointer items-start gap-3 rounded-lg border p-3 text-sm" :class="source === 'env' ? 'border-indigo-500 bg-indigo-50/50 ring-1 ring-indigo-500' : 'border-slate-200'">
                            <input type="radio" name="source" value="env" x-model="source" class="mt-0.5 text-indigo-600 focus:ring-indigo-500">
                            <span><span class="font-medium text-slate-900">File .env</span><span class="block text-xs text-slate-500">Memakai MAIL_* dari environment server (disarankan untuk production).</span></span>
                        </label>
                        <label class="flex cursor-pointer items-start gap-3 rounded-lg border p-3 text-sm" :class="source === 'database' ? 'border-indigo-500 bg-indigo-50/50 ring-1 ring-indigo-500' : 'border-slate-200'">
                            <input type="radio" name="source" value="database" x-model="source" class="mt-0.5 text-indigo-600 focus:ring-indigo-500">
                            <span><span class="font-medium text-slate-900">Diatur dari aplikasi</span><span class="block text-xs text-slate-500">Disimpan di database; password dienkripsi.</span></span>
                        </label>
                    </div>
                </div>

                <fieldset class="grid grid-cols-1 gap-4 sm:grid-cols-2" :disabled="source !== 'database'" :class="source !== 'database' && 'opacity-60'">
                    <div>
                        <label for="host" class="label">SMTP Host</label>
                        <input id="host" name="host" value="{{ old('host', $values['host']) }}" class="input @error('host') input-error @enderror" placeholder="smtp.usk.ac.id">
                        <x-field-error name="host" />
                    </div>
                    <div>
                        <label for="port" class="label">SMTP Port</label>
                        <input id="port" name="port" type="number" value="{{ old('port', $values['port']) }}" class="input @error('port') input-error @enderror" placeholder="587">
                        <x-field-error name="port" />
                    </div>
                    <div>
                        <label for="username" class="label">SMTP Username</label>
                        <input id="username" name="username" value="{{ old('username', $values['username']) }}" class="input" autocomplete="off">
                    </div>
                    <div>
                        <label for="password" class="label">SMTP Password</label>
                        <input id="password" name="password" type="password" value="" class="input" autocomplete="new-password" placeholder="{{ $values['has_password'] ? '•••••••• (tersimpan)' : '' }}">
                        <p class="help">Kosongkan untuk mempertahankan password lama. Password tidak pernah ditampilkan kembali.</p>
                        @if ($values['has_password'])
                            <label class="mt-1 flex items-center gap-2 text-xs text-slate-600"><input type="checkbox" name="clear_password" value="1" class="rounded border-slate-300"> Hapus password tersimpan</label>
                        @endif
                    </div>
                    <div>
                        <label for="encryption" class="label">Encryption</label>
                        <select id="encryption" name="encryption" class="input">
                            <option value="">Tidak ada</option>
                            <option value="tls" @selected(old('encryption', $values['encryption']) === 'tls')>TLS (STARTTLS, port 587)</option>
                            <option value="ssl" @selected(old('encryption', $values['encryption']) === 'ssl')>SSL (port 465)</option>
                        </select>
                    </div>
                    <div></div>
                    <div>
                        <label for="from_address" class="label">From Email</label>
                        <input id="from_address" name="from_address" type="email" value="{{ old('from_address', $values['from_address']) }}" class="input @error('from_address') input-error @enderror">
                        <x-field-error name="from_address" />
                    </div>
                    <div>
                        <label for="from_name" class="label">From Name</label>
                        <input id="from_name" name="from_name" value="{{ old('from_name', $values['from_name']) }}" class="input">
                    </div>
                </fieldset>

                <div>
                    <label for="admin_notification_address" class="label">Admin Notification Email</label>
                    <input id="admin_notification_address" name="admin_notification_address" value="{{ old('admin_notification_address', $values['admin_notification_address']) }}" class="input @error('admin_notification_address') input-error @enderror" placeholder="admin@usk.ac.id, sarpras@usk.ac.id">
                    <p class="help">Pisahkan dengan koma. Kosong = dikirim ke semua akun admin aktif.</p>
                    <x-field-error name="admin_notification_address" />
                </div>
            </div>
            <div class="flex justify-end border-t border-slate-100 px-5 py-4">
                <button class="btn btn-primary"><x-icon name="check" class="size-4" /> Simpan Pengaturan</button>
            </div>
        </form>

        <div class="space-y-6">
            <form method="POST" action="{{ route('admin.settings.email.test') }}" class="card">
                @csrf
                <div class="card-header"><h2 class="card-title">Test SMTP</h2></div>
                <div class="card-body space-y-3">
                    <p class="text-sm text-slate-500">Kirim email uji menggunakan konfigurasi yang tersimpan.</p>
                    <input name="test_email" type="email" required value="{{ old('test_email', auth()->user()->email) }}" class="input @error('test_email') input-error @enderror">
                    <x-field-error name="test_email" />
                    <button class="btn btn-secondary w-full"><x-icon name="send" class="size-4" /> Test SMTP</button>
                </div>
            </form>
            <div class="card card-body text-sm text-slate-600">
                <div class="flex items-start gap-2"><x-icon name="lock" class="mt-0.5 size-4 text-slate-400" />
                    <p>Password SMTP disimpan terenkripsi (APP_KEY), tidak ditampilkan ulang, dan tidak dicatat di log.</p></div>
                @if (app()->environment('local'))
                    <div class="mt-3 flex items-start gap-2"><x-icon name="info" class="mt-0.5 size-4 text-sky-500" />
                        <p>Development: email ditangkap Mailpit di <a class="text-indigo-600 underline" href="http://localhost:8025" target="_blank" rel="noopener">localhost:8025</a>.</p></div>
                @endif
            </div>
        </div>
    </div>
</x-layouts.app>
