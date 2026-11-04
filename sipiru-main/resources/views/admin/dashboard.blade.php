<x-layouts.app title="Dashboard Admin">
    <x-page-header title="Dashboard" :subtitle="'Periode aktif: '.($period?->year ?? 'belum ada').' · '.now()->translatedFormat('l, d F Y')" />

    @unless ($period)
        <div class="mb-5 flex items-center gap-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            <x-icon name="triangle-alert" class="size-4" /> Belum ada periode aktif — user tidak dapat mengajukan peminjaman.
            <a href="{{ route('admin.periods.index') }}" class="ml-auto font-medium underline">Atur periode</a>
        </div>
    @endunless

    <div class="grid grid-cols-2 gap-4 md:grid-cols-3 2xl:grid-cols-6">
        <x-stat label="Total Gedung" :value="$stats['buildings']" icon="building-2" :href="route('admin.buildings.index')" />
        <x-stat label="Total Ruangan" :value="$stats['rooms']" icon="door-open" tone="blue" :href="route('admin.rooms.index')" />
        <x-stat label="Menunggu Persetujuan" :value="$stats['pending']" icon="hourglass" tone="amber" :href="route('admin.bookings.index', ['status' => 'pending'])" />
        <x-stat label="Peminjaman Hari Ini" :value="$stats['today']" icon="calendar-days" tone="indigo" :href="route('admin.bookings.index', ['date' => today()->toDateString()])" />
        <x-stat label="Disetujui (aktif)" :value="$stats['approved']" icon="circle-check" tone="green" :href="route('admin.bookings.index', ['status' => 'approved'])" />
        <x-stat label="User" :value="$stats['users']" icon="users" tone="slate" :href="route('admin.users.index')" />
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Menunggu Persetujuan</h2>
                <a href="{{ route('admin.bookings.index', ['status' => 'pending']) }}" class="text-xs font-medium text-indigo-600">Lihat semua</a>
            </div>
            <ul class="divide-y divide-slate-100">
                @forelse ($pendingBookings as $b)
                    <li>
                        <a href="{{ route('admin.bookings.show', $b) }}" class="flex items-center gap-3 px-5 py-3 hover:bg-slate-50">
                            <div class="min-w-0 flex-1">
                                <div class="text-sm font-medium text-slate-900">{{ $b->user->name }} <span class="font-normal text-slate-500">· {{ $b->booking_number }}</span></div>
                                <div class="truncate text-xs text-slate-500">{{ $b->room->name }} ({{ $b->room->building->name }}) · {{ $b->start_at->translatedFormat('d M Y') }} {{ $b->timeLabel() }}</div>
                            </div>
                            <x-icon name="chevron-right" class="size-4 text-slate-400" />
                        </a>
                    </li>
                @empty
                    <li><x-empty message="Tidak ada pengajuan yang menunggu." icon="circle-check" /></li>
                @endforelse
            </ul>
        </div>
        <div class="card">
            <div class="card-header"><h2 class="card-title">Jadwal Hari Ini</h2><a href="{{ route('admin.calendar') }}" class="text-xs font-medium text-indigo-600">Kalender</a></div>
            <ul class="divide-y divide-slate-100">
                @forelse ($todayBookings as $b)
                    <li class="flex items-center gap-4 px-5 py-3">
                        <div class="w-24 shrink-0 text-sm font-semibold tabular-nums text-slate-900">{{ $b->timeLabel() }}</div>
                        <div class="min-w-0 flex-1">
                            <a href="{{ route('admin.bookings.show', $b) }}" class="text-sm font-medium hover:text-indigo-600">{{ $b->room->name }}</a>
                            <div class="truncate text-xs text-slate-500">{{ $b->room->building->name }} · {{ $b->user->name }}</div>
                        </div>
                        <x-status-badge :status="$b->status" />
                    </li>
                @empty
                    <li><x-empty message="Tidak ada peminjaman hari ini." icon="calendar-days" /></li>
                @endforelse
            </ul>
        </div>
    </div>
</x-layouts.app>
