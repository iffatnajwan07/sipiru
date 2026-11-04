<x-layouts.app :title="$building->name">
    <x-page-header :title="$building->name" :subtitle="'Kode: '.$building->code" :back="route('admin.buildings.index')">
        <x-slot:actions>
            <a href="{{ route('admin.rooms.create', ['building' => $building->id]) }}" class="btn btn-secondary"><x-icon name="plus" class="size-4" /> Tambah Ruangan</a>
            <a href="{{ route('admin.buildings.edit', $building) }}" class="btn btn-secondary"><x-icon name="pencil" class="size-4" /> Edit</a>
            <form method="POST" action="{{ route('admin.buildings.toggle', $building) }}">
                @csrf @method('PATCH')
                <button class="btn {{ $building->is_active ? 'btn-secondary text-amber-700' : 'btn-success' }}"><x-icon name="power" class="size-4" /> {{ $building->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
            </form>
        </x-slot:actions>
    </x-page-header>


    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <div class="card">
                <div class="card-header"><h2 class="card-title">Ruangan ({{ $rooms->count() }})</h2></div>
                @if ($rooms->isEmpty())
                    <x-empty message="Gedung ini belum memiliki ruangan." icon="door-open" />
                @else
                    <div class="overflow-x-auto">
                        <table class="table">
                            <thead><tr><th>Kode</th><th>Nama</th><th>Lantai</th><th>Kapasitas</th><th>Status</th></tr></thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($rooms as $room)
                                    <tr>
                                        <td class="font-mono text-xs">{{ $room->code }}</td>
                                        <td><a href="{{ route('admin.rooms.show', $room) }}" class="font-medium hover:text-indigo-600">{{ $room->name }}</a></td>
                                        <td>{{ $room->floor ?? '-' }}</td>
                                        <td>{{ $room->capacity }}</td>
                                        <td><x-active-badge :active="$room->is_active" /></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
            <div class="card">
                <div class="card-header"><h2 class="card-title">Activity Log</h2></div>
                @include('admin._activity-list')
            </div>
        </div>
        <div class="space-y-6">
            <div class="card card-body">
                <dl class="divide-y divide-slate-100">
                    <div class="dl-row"><dt>Status</dt><dd><x-active-badge :active="$building->is_active" /></dd></div>
                    <div class="dl-row"><dt>Alamat</dt><dd class="font-normal">{{ $building->address ?: '-' }}</dd></div>
                    <div class="dl-row"><dt>Deskripsi</dt><dd class="font-normal">{{ $building->description ?: '-' }}</dd></div>
                    <div class="dl-row"><dt>Dibuat</dt><dd class="font-normal">{{ $building->created_at->translatedFormat('d M Y H:i') }}</dd></div>
                    <div class="dl-row"><dt>Diubah</dt><dd class="font-normal">{{ $building->updated_at->translatedFormat('d M Y H:i') }}</dd></div>
                </dl>
            </div>
            <div class="card card-body text-sm">
                <h3 class="font-semibold text-slate-900">Hapus Gedung</h3>
                <p class="mt-1 text-slate-500">Gedung hanya dapat dihapus bila tidak memiliki ruangan. Untuk menjaga data historis, gunakan <em>Nonaktifkan</em>.</p>
                <form method="POST" action="{{ route('admin.buildings.destroy', $building) }}" class="mt-3" onsubmit="return confirm('Hapus gedung {{ $building->name }}?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-secondary text-red-600" @disabled($rooms->isNotEmpty())><x-icon name="trash-2" class="size-4" /> Hapus</button>
                </form>
            </div>
        </div>
    </div>
</x-layouts.app>
