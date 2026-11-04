<x-layouts.app title="Dashboard">
    <x-page-header :title="'Halo, '.auth()->user()->name" subtitle="Ringkasan peminjaman ruangan Anda.">
        <x-slot:actions>
            <a href="{{ route('bookings.create') }}" class="btn btn-primary"><x-icon name="calendar-plus" class="size-4" /> Ajukan Peminjaman</a>
        </x-slot:actions>
    </x-page-header>

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-stat label="Pengajuan Saya" :value="$stats['total']" icon="clipboard-list" :href="route('bookings.index')" />
        <x-stat label="Disetujui" :value="$stats['approved']" icon="circle-check" tone="green" :href="route('bookings.index', ['status' => 'approved'])" />
        <x-stat label="Menunggu" :value="$stats['pending']" icon="hourglass" tone="amber" :href="route('bookings.index', ['status' => 'pending'])" />
        <x-stat label="Ditolak" :value="$stats['rejected']" icon="circle-x" tone="red" :href="route('bookings.index', ['status' => 'rejected'])" />
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-5">
        <div class="card lg:col-span-3">
            <div class="card-header"><h2 class="card-title">Peminjaman Terdekat</h2></div>
            @forelse ($upcoming as $booking)
                <a href="{{ route('bookings.show', $booking) }}" class="flex items-center gap-4 border-b border-slate-100 px-5 py-4 last:border-0 hover:bg-slate-50">
                    <div class="w-14 shrink-0 rounded-lg border border-slate-200 text-center">
                        <div class="rounded-t-lg bg-indigo-600 py-0.5 text-[10px] font-semibold uppercase text-white">{{ $booking->start_at->translatedFormat('M') }}</div>
                        <div class="py-1 text-lg font-semibold text-slate-900">{{ $booking->start_at->format('d') }}</div>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="truncate font-medium text-slate-900">{{ $booking->room->name }}</div>
                        <div class="truncate text-sm text-slate-500">{{ $booking->room->building->name }} · {{ $booking->timeLabel() }}</div>
                    </div>
                    <x-status-badge :status="$booking->status" />
                </a>
            @empty
                <x-empty message="Tidak ada peminjaman mendatang.">
                    <a href="{{ route('bookings.create') }}" class="btn btn-secondary btn-sm"><x-icon name="plus" class="size-4" /> Ajukan sekarang</a>
                </x-empty>
            @endforelse
        </div>
        <div class="card lg:col-span-2">
            <div class="card-header">
                <h2 class="card-title">Aktivitas Terbaru</h2>
                <a href="{{ route('bookings.index') }}" class="text-xs font-medium text-indigo-600 hover:text-indigo-700">Lihat semua</a>
            </div>
            <ul class="divide-y divide-slate-100">
                @forelse ($recent as $booking)
                    <li class="flex items-center justify-between gap-3 px-5 py-3 text-sm">
                        <div class="min-w-0">
                            <a href="{{ route('bookings.show', $booking) }}" class="font-medium text-slate-900 hover:text-indigo-600">{{ $booking->booking_number }}</a>
                            <div class="truncate text-xs text-slate-500">{{ $booking->room->name }} · {{ $booking->start_at->translatedFormat('d M Y') }}</div>
                        </div>
                        <x-status-badge :status="$booking->status" />
                    </li>
                @empty
                    <li><x-empty message="Belum ada pengajuan." /></li>
                @endforelse
            </ul>
        </div>
    </div>
</x-layouts.app>
