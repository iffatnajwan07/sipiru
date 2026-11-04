<x-layouts.app title="Manajemen Gedung">
    <x-page-header title="Manajemen Gedung" subtitle="Kelola data gedung. Nama & kode gedung dijamin unik.">
        <x-slot:actions>
            <a href="{{ route('admin.buildings.create') }}" class="btn btn-primary"><x-icon name="plus" class="size-4" /> Tambah Gedung</a>
        </x-slot:actions>
    </x-page-header>

    <div class="card">
        <form method="GET" class="flex flex-col gap-3 border-b border-slate-100 p-4 sm:flex-row">
            <div class="relative flex-1">
                <span class="pointer-events-none absolute inset-y-0 left-3 grid place-items-center text-slate-400"><x-icon name="search" class="size-4" /></span>
                <input name="q" value="{{ request('q') }}" placeholder="Cari nama atau kode gedung…" class="input pl-9">
            </div>
            <select name="status" class="input sm:w-44" onchange="this.form.submit()">
                <option value="">Semua status</option>
                <option value="active" @selected(request('status') === 'active')>Aktif</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Nonaktif</option>
            </select>
            <button class="btn btn-secondary"><x-icon name="funnel" class="size-4" /> Filter</button>
        </form>
        @if ($buildings->isEmpty())
            <x-empty message="Belum ada gedung." icon="building-2" />
        @else
            <div class="overflow-x-auto">
                <table class="table">
                    <thead><tr><th>Kode</th><th>Nama Gedung</th><th>Jumlah Ruangan</th><th>Status</th><th>Updated At</th><th class="text-right">Aksi</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($buildings as $building)
                            <tr>
                                <td><span class="rounded bg-slate-100 px-1.5 py-0.5 font-mono text-xs font-semibold text-slate-700">{{ $building->code }}</span></td>
                                <td><a href="{{ route('admin.buildings.show', $building) }}" class="font-medium text-slate-900 hover:text-indigo-600">{{ $building->name }}</a>
                                    @if ($building->address)<div class="max-w-xs truncate text-xs text-slate-500">{{ $building->address }}</div>@endif</td>
                                <td class="tabular-nums">{{ $building->rooms_count }}</td>
                                <td><x-active-badge :active="$building->is_active" /></td>
                                <td class="whitespace-nowrap text-slate-500">{{ $building->updated_at->translatedFormat('d M Y H:i') }}</td>
                                <td>
                                    <div class="flex justify-end gap-1">
                                        <a href="{{ route('admin.buildings.show', $building) }}" class="btn btn-ghost btn-sm" title="Lihat ruangan & log"><x-icon name="eye" class="size-4" /></a>
                                        <a href="{{ route('admin.buildings.edit', $building) }}" class="btn btn-ghost btn-sm" title="Edit"><x-icon name="pencil" class="size-4" /></a>
                                        <form method="POST" action="{{ route('admin.buildings.toggle', $building) }}">
                                            @csrf @method('PATCH')
                                            <button class="btn btn-ghost btn-sm {{ $building->is_active ? 'text-amber-600' : 'text-emerald-600' }}" title="{{ $building->is_active ? 'Nonaktifkan' : 'Aktifkan' }}"><x-icon name="power" class="size-4" /></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-4 py-3">{{ $buildings->links() }}</div>
        @endif
    </div>
</x-layouts.app>
