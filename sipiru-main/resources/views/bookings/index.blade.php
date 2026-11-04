<x-layouts.app title="Peminjaman Saya">
    <x-page-header title="Peminjaman Saya" subtitle="Riwayat pengajuan peminjaman ruangan Anda.">
        <x-slot:actions>
            <a href="{{ route('bookings.create') }}" class="btn btn-primary"><x-icon name="plus" class="size-4" /> Ajukan</a>
        </x-slot:actions>
    </x-page-header>

    <div class="card">
        <form method="GET" class="flex flex-col gap-3 border-b border-slate-100 p-4 sm:flex-row">
            <div class="relative flex-1">
                <span class="pointer-events-none absolute inset-y-0 left-3 grid place-items-center text-slate-400"><x-icon name="search" class="size-4" /></span>
                <input name="q" value="{{ request('q') }}" placeholder="Cari nomor peminjaman…" class="input pl-9">
            </div>
            <select name="status" class="input sm:w-48" onchange="this.form.submit()">
                <option value="">Semua status</option>
                @foreach (\App\Enums\BookingStatus::cases() as $s)
                    <option value="{{ $s->value }}" @selected(request('status') === $s->value)>{{ $s->label() }}</option>
                @endforeach
            </select>
            <button class="btn btn-secondary"><x-icon name="funnel" class="size-4" /> Filter</button>
        </form>

        @if ($bookings->isEmpty())
            <x-empty message="Belum ada peminjaman." />
        @else
            <div class="overflow-x-auto">
                <table class="table">
                    <thead><tr><th>Booking</th><th>Gedung</th><th>Ruangan</th><th>Tanggal</th><th>Waktu</th><th>Status</th><th>Catatan</th><th class="text-right">Aksi</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($bookings as $booking)
                            <tr>
                                <td class="font-medium text-slate-900">{{ $booking->booking_number }}</td>
                                <td>{{ $booking->room->building->name }}</td>
                                <td>{{ $booking->room->name }}</td>
                                <td class="whitespace-nowrap">{{ $booking->start_at->translatedFormat('d M Y') }}</td>
                                <td class="whitespace-nowrap">{{ $booking->timeLabel() }}</td>
                                <td><x-status-badge :status="$booking->status" /></td>
                                <td class="max-w-48 truncate text-slate-500" title="{{ $booking->notes }}">{{ $booking->notes ?: '-' }}</td>
                                <td>
                                    <div class="flex justify-end gap-1">
                                        <a href="{{ route('bookings.show', $booking) }}" class="btn btn-ghost btn-sm" title="Detail"><x-icon name="eye" class="size-4" /></a>
                                        @can('downloadProof', $booking)
                                            <a href="{{ route('bookings.pdf', $booking) }}" class="btn btn-ghost btn-sm" title="Cetak Bukti"><x-icon name="printer" class="size-4" /></a>
                                        @endcan
                                        @can('cancel', $booking)
                                            <form method="POST" action="{{ route('bookings.cancel', $booking) }}" onsubmit="return confirm('Batalkan peminjaman {{ $booking->booking_number }}?')">
                                                @csrf
                                                <button class="btn btn-ghost btn-sm text-red-600 hover:bg-red-50 hover:text-red-700" title="Batalkan"><x-icon name="ban" class="size-4" /></button>
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-4 py-3">{{ $bookings->links() }}</div>
        @endif
    </div>
</x-layouts.app>
