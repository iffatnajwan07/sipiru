<x-layouts.app :title="$user->name">
    <x-page-header :title="$user->name" :subtitle="$user->user_type->label().' · '.$user->email" :back="route('admin.users.index')">
        <x-slot:actions>
            @can('toggleActive', $user)
                <form method="POST" action="{{ route('admin.users.toggle', $user) }}">
                    @csrf @method('PATCH')
                    <button class="btn {{ $user->is_active ? 'btn-secondary text-amber-700' : 'btn-success' }}"><x-icon name="power" class="size-4" /> {{ $user->is_active ? 'Nonaktifkan Akun' : 'Aktifkan Akun' }}</button>
                </form>
            @endcan
        </x-slot:actions>
    </x-page-header>
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="card card-body self-start">
            <dl class="divide-y divide-slate-100">
                <div class="dl-row"><dt>Status</dt><dd><x-active-badge :active="$user->is_active" /></dd></div>
                <div class="dl-row"><dt>{{ $user->identifierLabel() }}</dt><dd class="font-mono">{{ $user->identifier() ?? '-' }}</dd></div>
                <div class="dl-row"><dt>No. HP</dt><dd>{{ $user->phone ?? '-' }}</dd></div>
                @if ($user->position)<div class="dl-row"><dt>Jabatan</dt><dd>{{ $user->position }}</dd></div>@endif
                <div class="dl-row"><dt>Kebijakan</dt><dd class="font-normal">{{ $user->policy_accepted_at ? $user->policy_accepted_at->translatedFormat('d M Y').' (v'.$user->policy_version.')' : 'Belum disetujui' }}</dd></div>
                <div class="dl-row"><dt>Terdaftar</dt><dd class="font-normal">{{ $user->created_at->translatedFormat('d M Y') }}</dd></div>
                <div class="dl-row"><dt>Login terakhir</dt><dd class="font-normal">{{ $user->last_login_at?->translatedFormat('d M Y H:i') ?? '-' }}</dd></div>
            </dl>
            @can('viewKtp', $user)
                <a href="{{ route('admin.users.ktp', $user) }}" target="_blank" class="btn btn-secondary mt-4 w-full"><x-icon name="id-card" class="size-4" /> Lihat KTP</a>
            @endcan
        </div>
        <div class="space-y-6 lg:col-span-2">
            <div class="card">
                <div class="card-header"><h2 class="card-title">Peminjaman Terakhir</h2><a href="{{ route('admin.bookings.index', ['q' => $user->email]) }}" class="text-xs font-medium text-indigo-600">Lihat semua</a></div>
                <ul class="divide-y divide-slate-100">
                    @forelse ($bookings as $b)
                        <li class="flex items-center gap-3 px-5 py-3 text-sm">
                            <div class="min-w-0 flex-1"><a href="{{ route('admin.bookings.show', $b) }}" class="font-medium hover:text-indigo-600">{{ $b->booking_number }}</a>
                                <div class="text-xs text-slate-500">{{ $b->room->name }} ({{ $b->room->building->name }}) · {{ $b->start_at->translatedFormat('d M Y') }} {{ $b->timeLabel() }}</div></div>
                            <x-status-badge :status="$b->status" />
                        </li>
                    @empty
                        <li><x-empty message="Belum ada peminjaman." /></li>
                    @endforelse
                </ul>
            </div>
            <div class="card">
                <div class="card-header"><h2 class="card-title">Login Activity</h2></div>
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead><tr><th>Login</th><th>Logout</th><th>Metode</th><th>IP</th></tr></thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($logins as $l)
                                <tr><td>{{ $l->login_at->translatedFormat('d M Y H:i') }}</td><td>{{ $l->logout_at?->translatedFormat('d M Y H:i') ?? '-' }}</td><td>{{ $l->method }}</td><td class="font-mono text-xs">{{ $l->ip_address }}</td></tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-slate-500">Belum ada data.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
