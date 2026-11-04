<x-layouts.app title="Users">
    <x-page-header title="Users" subtitle="Admin, pegawai USK, dan pengguna eksternal." />
    <div class="card">
        <form method="GET" class="flex flex-col gap-3 border-b border-slate-100 p-4 sm:flex-row">
            <div class="relative flex-1">
                <span class="pointer-events-none absolute inset-y-0 left-3 grid place-items-center text-slate-400"><x-icon name="search" class="size-4" /></span>
                <input name="q" value="{{ request('q') }}" placeholder="Nama, email, NIP, NIK…" class="input pl-9">
            </div>
            <select name="type" class="input sm:w-44">
                <option value="">Semua jenis</option>
                @foreach (\App\Enums\UserType::cases() as $t)<option value="{{ $t->value }}" @selected(request('type') === $t->value)>{{ $t->label() }}</option>@endforeach
            </select>
            <select name="status" class="input sm:w-36">
                <option value="">Status</option>
                <option value="active" @selected(request('status') === 'active')>Aktif</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Nonaktif</option>
            </select>
            <button class="btn btn-secondary"><x-icon name="funnel" class="size-4" /> Filter</button>
        </form>
        <div class="overflow-x-auto">
            <table class="table">
                <thead><tr><th>Nama</th><th>Jenis</th><th>NIP/NIK</th><th>No. HP</th><th>Peminjaman</th><th>Login Terakhir</th><th>Status</th><th></th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($users as $u)
                        <tr>
                            <td><a href="{{ route('admin.users.show', $u) }}" class="font-medium text-slate-900 hover:text-indigo-600">{{ $u->name }}</a><div class="text-xs text-slate-500">{{ $u->email }}</div></td>
                            <td><x-badge :tone="$u->isAdmin() ? 'indigo' : ($u->isPegawai() ? 'blue' : 'slate')">{{ $u->user_type->label() }}</x-badge></td>
                            <td class="font-mono text-xs">{{ $u->identifier() ?? '-' }}</td>
                            <td>{{ $u->phone ?? '-' }}</td>
                            <td class="tabular-nums">{{ $u->bookings_count }}</td>
                            <td class="whitespace-nowrap text-slate-500">{{ $u->last_login_at?->translatedFormat('d M Y H:i') ?? '-' }}</td>
                            <td><x-active-badge :active="$u->is_active" /></td>
                            <td class="text-right"><a href="{{ route('admin.users.show', $u) }}" class="btn btn-ghost btn-sm"><x-icon name="eye" class="size-4" /></a></td>
                        </tr>
                    @empty
                        <tr><td colspan="8"><x-empty message="Tidak ada user." icon="users" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 px-4 py-3">{{ $users->links() }}</div>
    </div>
</x-layouts.app>
