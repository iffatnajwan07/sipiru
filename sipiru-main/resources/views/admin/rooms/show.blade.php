<x-layouts.app :title="$room->name">
    <x-page-header :title="$room->name" :subtitle="$room->building->name.' · '.$room->code" :back="route('admin.rooms.index')">
        <x-slot:actions>
            <a href="{{ route('admin.rooms.edit', $room) }}" class="btn btn-secondary"><x-icon name="pencil" class="size-4" /> Edit</a>
            <form method="POST" action="{{ route('admin.rooms.toggle', $room) }}">
                @csrf @method('PATCH')
                <button class="btn {{ $room->is_active ? 'btn-secondary text-amber-700' : 'btn-success' }}"><x-icon name="power" class="size-4" /> {{ $room->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
            </form>
        </x-slot:actions>
    </x-page-header>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <div class="card card-body">
                <dl class="grid grid-cols-1 gap-x-8 sm:grid-cols-2">
                    <div class="dl-row"><dt>Gedung</dt><dd><a href="{{ route('admin.buildings.show', $room->building) }}" class="hover:text-indigo-600">{{ $room->building->name }}</a></dd></div>
                    <div class="dl-row"><dt>Kode</dt><dd class="font-mono">{{ $room->code }}</dd></div>
                    <div class="dl-row"><dt>Lantai</dt><dd>{{ $room->floor ?? '-' }}</dd></div>
                    <div class="dl-row"><dt>Kapasitas</dt><dd>{{ $room->capacity }} orang</dd></div>
                    <div class="dl-row"><dt>Status</dt><dd><x-active-badge :active="$room->is_active" /> @unless ($room->building->is_active)<x-badge tone="amber">gedung nonaktif</x-badge>@endunless</dd></div>
                    <div class="dl-row"><dt>Diubah</dt><dd class="font-normal">{{ $room->updated_at->translatedFormat('d M Y H:i') }}</dd></div>
                </dl>
                @if ($room->description)<p class="mt-3 border-t border-slate-100 pt-3 text-sm text-slate-600">{{ $room->description }}</p>@endif
            </div>
            <div class="card">
                <div class="card-header"><h2 class="card-title">Peminjaman Terakhir</h2><a href="{{ route('admin.bookings.index', ['room' => $room->id]) }}" class="text-xs font-medium text-indigo-600">Lihat semua</a></div>
                <ul class="divide-y divide-slate-100">
                    @forelse ($bookings as $b)
                        <li class="flex items-center gap-3 px-5 py-3 text-sm">
                            <div class="min-w-0 flex-1"><a href="{{ route('admin.bookings.show', $b) }}" class="font-medium hover:text-indigo-600">{{ $b->booking_number }}</a>
                                <div class="text-xs text-slate-500">{{ $b->user->name }} · {{ $b->start_at->translatedFormat('d M Y') }} {{ $b->timeLabel() }}</div></div>
                            <x-status-badge :status="$b->status" />
                        </li>
                    @empty
                        <li><x-empty message="Belum ada peminjaman." /></li>
                    @endforelse
                </ul>
            </div>
            <div class="card">
                <div class="card-header"><h2 class="card-title">Activity Log</h2></div>
                @include('admin._activity-list')
            </div>
        </div>
        <div class="space-y-6">
            <div class="card card-body text-center">
                <h3 class="text-sm font-semibold">QR Ruangan</h3>
                <p class="mt-1 text-xs text-slate-500">Scan untuk mengajukan peminjaman ruangan ini.</p>
                <div class="mx-auto mt-3 w-44 [&_svg]:h-auto [&_svg]:w-full">{!! $qrSvg !!}</div>
                <div class="mt-4 grid grid-cols-2 gap-2">
                    <a href="{{ route('admin.rooms.qr', $room) }}" class="btn btn-secondary btn-sm"><x-icon name="printer" class="size-4" /> Cetak</a>
                    <a href="{{ route('admin.rooms.qr-png', $room) }}" class="btn btn-secondary btn-sm"><x-icon name="download" class="size-4" /> PNG</a>
                </div>
            </div>
            <div class="card card-body text-sm">
                <h3 class="font-semibold text-slate-900">Hapus Ruangan</h3>
                <p class="mt-1 text-slate-500">Ruangan yang memiliki riwayat peminjaman tidak dapat dihapus. Gunakan <em>Nonaktifkan</em>.</p>
                <form method="POST" action="{{ route('admin.rooms.destroy', $room) }}" class="mt-3" onsubmit="return confirm('Hapus ruangan {{ $room->name }}?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-secondary text-red-600" @disabled($bookings->isNotEmpty())><x-icon name="trash-2" class="size-4" /> Hapus</button>
                </form>
            </div>
        </div>
    </div>
</x-layouts.app>
