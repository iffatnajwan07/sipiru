<x-layouts.app title="Periode">
    <x-page-header title="Periode" subtitle="Hanya satu periode yang dapat aktif. Mengaktifkan periode baru otomatis menonaktifkan periode lain." />
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="card lg:col-span-2">
            @if ($periods->isEmpty())
                <x-empty message="Belum ada periode." icon="calendar-range" />
            @else
                <table class="table">
                    <thead><tr><th>Tahun</th><th>Status</th><th>Jumlah Peminjaman</th><th class="text-right">Aksi</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($periods as $period)
                            <tr>
                                <td class="text-base font-semibold text-slate-900">{{ $period->year }}</td>
                                <td><x-badge :tone="$period->isActive() ? 'green' : 'slate'">{{ $period->status->label() }}</x-badge></td>
                                <td class="tabular-nums">{{ $period->bookings_count }}</td>
                                <td class="text-right">
                                    @if ($period->isActive())
                                        <form method="POST" action="{{ route('admin.periods.deactivate', $period) }}" onsubmit="return confirm('Nonaktifkan periode {{ $period->year }}? User tidak dapat mengajukan peminjaman sampai ada periode aktif.')">
                                            @csrf @method('PATCH')
                                            <button class="btn btn-secondary btn-sm">Nonaktifkan</button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('admin.periods.activate', $period) }}">
                                            @csrf @method('PATCH')
                                            <button class="btn btn-primary btn-sm">Aktifkan</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
        <form method="POST" action="{{ route('admin.periods.store') }}" class="card self-start">
            @csrf
            <div class="card-header"><h2 class="card-title">Tambah Periode</h2></div>
            <div class="card-body space-y-4">
                <div>
                    <label for="year" class="label">Tahun</label>
                    <input id="year" name="year" type="number" min="2000" max="2100" value="{{ old('year', now()->year) }}" required class="input @error('year') input-error @enderror">
                    <x-field-error name="year" />
                </div>
                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" name="activate" value="1" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"> Langsung aktifkan
                </label>
                <button class="btn btn-primary w-full"><x-icon name="plus" class="size-4" /> Tambah</button>
            </div>
        </form>
    </div>
</x-layouts.app>
