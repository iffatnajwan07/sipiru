<x-layouts.app title="Login Activity">
    <x-page-header title="Login Activity" subtitle="Riwayat login & logout seluruh pengguna." />
    <div class="card">
        <form method="GET" class="flex gap-3 border-b border-slate-100 p-4">
            <input name="q" value="{{ request('q') }}" placeholder="Cari nama / email…" class="input flex-1">
            <button class="btn btn-secondary"><x-icon name="search" class="size-4" /> Cari</button>
        </form>
        <div class="overflow-x-auto">
            <table class="table">
                <thead><tr><th>User</th><th>Metode</th><th>Login</th><th>Logout</th><th>IP Address</th><th>User Agent</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($logs as $l)
                        <tr>
                            <td>{{ $l->user?->name }}<div class="text-xs text-slate-500">{{ $l->user?->user_type->label() }}</div></td>
                            <td><span class="font-mono text-xs">{{ $l->method }}</span></td>
                            <td class="whitespace-nowrap">{{ $l->login_at->translatedFormat('d M Y H:i:s') }}</td>
                            <td class="whitespace-nowrap">{{ $l->logout_at?->translatedFormat('d M Y H:i:s') ?? '-' }}</td>
                            <td class="font-mono text-xs">{{ $l->ip_address }}</td>
                            <td class="max-w-xs truncate text-xs text-slate-500" title="{{ $l->user_agent }}">{{ $l->user_agent }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><x-empty message="Belum ada data login." icon="log-in" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 px-4 py-3">{{ $logs->links() }}</div>
    </div>
</x-layouts.app>
