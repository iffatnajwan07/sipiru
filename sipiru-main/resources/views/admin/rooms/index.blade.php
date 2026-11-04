<x-layouts.app title="Manajemen Ruang">
    <x-page-header title="Manajemen Ruang" subtitle="Nama & kode ruang unik di dalam gedungnya.">
        <x-slot:actions>
            <a href="{{ route('admin.rooms.create') }}" class="btn btn-primary"><x-icon name="plus" class="size-4" /> Tambah Ruangan</a>
        </x-slot:actions>
    </x-page-header>

    <div class="card">
        <form method="GET" class="grid grid-cols-1 gap-3 border-b border-slate-100 p-4 sm:grid-cols-2 lg:grid-cols-6">
            <div class="relative lg:col-span-2">
                <span class="pointer-events-none absolute inset-y-0 left-3 grid place-items-center text-slate-400"><x-icon name="search" class="size-4" /></span>
                <input name="q" value="{{ request('q') }}" placeholder="Cari nama atau kode…" class="input pl-9">
            </div>
            <select name="building" class="input">
                <option value="">Semua gedung</option>
                @foreach ($buildings as $b)<option value="{{ $b->id }}" @selected(request('building') == $b->id)>{{ $b->name }}</option>@endforeach
            </select>
            <select name="floor" class="input">
                <option value="">Semua lantai</option>
                @foreach ($floors as $f)<option value="{{ $f }}" @selected(request('floor') === (string) $f)>Lantai {{ $f }}</option>@endforeach
            </select>
            <input name="min_capacity" type="number" min="1" value="{{ request('min_capacity') }}" placeholder="Kapasitas min." class="input">
            <div class="flex gap-2">
                <select name="status" class="input">
                    <option value="">Status</option>
                    <option value="active" @selected(request('status') === 'active')>Aktif</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>Nonaktif</option>
                </select>
                <button class="btn btn-secondary" title="Filter"><x-icon name="funnel" class="size-4" /></button>
            </div>
        </form>
        @if ($rooms->isEmpty())
            <x-empty message="Tidak ada ruangan." icon="door-open" />
        @else
            <div class="overflow-x-auto">
                <table class="table">
                    {{-- 1. Tambahkan <th>Harga</th> di header tabel --}}
                    <thead><tr><th>Kode</th><th>Nama</th><th>Gedung</th><th>Lantai</th><th>Kapasitas</th><th>Harga</th><th>Status</th><th class="text-right">Aksi</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($rooms as $room)
                            <tr>
                                <td><span class="rounded bg-slate-100 px-1.5 py-0.5 font-mono text-xs font-semibold text-slate-700">{{ $room->code }}</span></td>
                                <td><a href="{{ route('admin.rooms.show', $room) }}" class="font-medium text-slate-900 hover:text-indigo-600">{{ $room->name }}</a></td>
                                <td>{{ $room->building->name }} @unless ($room->building->is_active)<x-badge>gedung nonaktif</x-badge>@endunless</td>
                                <td>{{ $room->floor ?? '-' }}</td>
                                <td class="tabular-nums">{{ $room->capacity }}</td>
                                
                                {{-- 2. Tambahkan format kolom Harga di sini --}}
                                <td class="tabular-nums whitespace-nowrap">
                                    {{ $room->price ? 'Rp ' . number_format($room->price, 0, ',', '.') : '-' }}
                                </td>
                                
                                <td><x-active-badge :active="$room->is_active" /></td>
                                <td>
                                    <div class="flex justify-end gap-1">
                                        <a href="{{ route('admin.rooms.show', $room) }}" class="btn btn-ghost btn-sm" title="Detail & log"><x-icon name="eye" class="size-4" /></a>
                                        <a href="{{ route('admin.rooms.edit', $room) }}" class="btn btn-ghost btn-sm" title="Edit"><x-icon name="pencil" class="size-4" /></a>
                                        <a href="{{ route('admin.rooms.qr', $room) }}" class="btn btn-ghost btn-sm" title="QR Code"><x-icon name="qr-code" class="size-4" /></a>
                                        <form method="POST" action="{{ route('admin.rooms.toggle', $room) }}">
                                            @csrf @method('PATCH')
                                            <button class="btn btn-ghost btn-sm {{ $room->is_active ? 'text-amber-600' : 'text-emerald-600' }}" title="{{ $room->is_active ? 'Nonaktifkan' : 'Aktifkan' }}"><x-icon name="power" class="size-4" /></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-4 py-3">{{ $rooms->links() }}</div>
        @endif
    </div>
</x-layouts.app>