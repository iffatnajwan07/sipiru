<x-layouts.app title="Reports">
    <x-page-header title="Reports" subtitle="Laporan peminjaman & pemakaian ruangan.">
        <x-slot:actions>
            <a href="{{ route('admin.reports.csv', request()->query()) }}" class="btn btn-secondary"><x-icon name="file-spreadsheet" class="size-4" /> Export CSV/Excel</a>
            <a href="{{ route('admin.reports.pdf', request()->query()) }}" class="btn btn-primary"><x-icon name="file-text" class="size-4" /> Export PDF</a>
        </x-slot:actions>
    </x-page-header>

    <form method="GET" class="card mb-6 grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-8">
        <select name="period" class="input">
            <option value="">Semua periode</option>
            @foreach ($periods as $p)<option value="{{ $p->id }}" @selected(($filters['period'] ?? null) == $p->id)>{{ $p->year }}</option>@endforeach
        </select>
        <select name="building" class="input">
            <option value="">Semua gedung</option>
            @foreach ($buildings as $b)<option value="{{ $b->id }}" @selected(($filters['building'] ?? null) == $b->id)>{{ $b->name }}</option>@endforeach
        </select>
        <select name="room" class="input">
            <option value="">Semua ruangan</option>
            @foreach ($rooms as $r)<option value="{{ $r->id }}" @selected(($filters['room'] ?? null) == $r->id)>{{ $r->name }} ({{ $r->building?->code }})</option>@endforeach
        </select>
        <select name="status" class="input">
            <option value="">Semua status</option>
            @foreach (\App\Enums\BookingStatus::cases() as $s)<option value="{{ $s->value }}" @selected(($filters['status'] ?? null) === $s->value)>{{ $s->label() }}</option>@endforeach
        </select>
        <select name="user" class="input">
            <option value="">Semua user</option>
            @foreach ($users as $u)<option value="{{ $u->id }}" @selected(($filters['user'] ?? null) == $u->id)>{{ $u->name }}</option>@endforeach
        </select>
        <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="input" title="Dari tanggal">
        <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="input" title="Sampai tanggal">
        <div class="flex gap-2">
            <button class="btn btn-primary flex-1"><x-icon name="funnel" class="size-4" /> Terapkan</button>
            <a href="{{ route('admin.reports.index') }}" class="btn btn-secondary" title="Reset"><x-icon name="x" class="size-4" /></a>
        </div>
        <x-field-error name="to" />
    </form>

    <div class="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-5">
        <x-stat label="Jumlah Peminjaman" :value="$summary['total']" icon="clipboard-list" />
        <x-stat label="Approved / Selesai" :value="$summary['approved']" icon="circle-check" tone="green" />
        <x-stat label="Menunggu" :value="$summary['pending']" icon="hourglass" tone="amber" />
        <x-stat label="Rejected" :value="$summary['rejected']" icon="circle-x" tone="red" />
        <x-stat label="Cancelled" :value="$summary['cancelled']" icon="ban" tone="slate" />
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-5">
        <div class="card xl:col-span-2">
            <div class="card-header"><h2 class="card-title">Pemakaian Ruangan</h2><span class="text-xs text-slate-500">approved + selesai</span></div>
            @if ($usage->isEmpty())
                <x-empty message="Belum ada pemakaian." icon="chart-column" />
            @else
                @php($maxHours = max($usage->max('hours'), 1))
                <ul class="space-y-3 p-5">
                    @foreach ($usage as $row)
                        <li>
                            <div class="flex items-baseline justify-between gap-3 text-sm">
                                <span class="truncate font-medium">{{ $row['room'] }} <span class="font-normal text-slate-500">· {{ $row['building'] }}</span></span>
                                <span class="shrink-0 tabular-nums text-slate-600">{{ $row['count'] }}× · {{ $row['hours'] }} jam</span>
                            </div>
                            <div class="mt-1 h-2 rounded-full bg-slate-100"><div class="h-2 rounded-full bg-indigo-500" style="width: {{ max(2, round($row['hours'] / $maxHours * 100)) }}%"></div></div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
        <div class="card xl:col-span-3">
            <div class="card-header"><h2 class="card-title">Detail Peminjaman</h2></div>
            <div class="overflow-x-auto">
                <table class="table">
                    <thead><tr><th>Nomor</th><th>Pemohon</th><th>Ruangan</th><th>Tanggal</th><th>Status</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($bookings as $b)
                            <tr>
                                <td><a href="{{ route('admin.bookings.show', $b) }}" class="font-medium hover:text-indigo-600">{{ $b->booking_number }}</a></td>
                                <td>{{ $b->user->name }}</td>
                                <td>{{ $b->room->name }}<div class="text-xs text-slate-500">{{ $b->room->building->name }}</div></td>
                                <td class="whitespace-nowrap">{{ $b->start_at->translatedFormat('d M Y') }}<div class="text-xs text-slate-500">{{ $b->timeLabel() }}</div></td>
                                <td><x-status-badge :status="$b->status" /></td>
                            </tr>
                        @empty
                            <tr><td colspan="5"><x-empty message="Tidak ada data." /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-4 py-3">{{ $bookings->links() }}</div>
        </div>
    </div>
</x-layouts.app>
