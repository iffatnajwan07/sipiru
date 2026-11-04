@php($pendingOnly = request('status') === 'pending')
<x-layouts.app :title="$pendingOnly ? 'Menunggu Persetujuan' : 'Data Peminjaman'">
    <x-page-header :title="$pendingOnly ? 'Menunggu Persetujuan' : 'Semua Peminjaman'" :subtitle="$pendingOnly ? 'Diurutkan dari waktu peminjaman terdekat.' : 'Seluruh pengajuan peminjaman ruangan.'" />

    <div class="card">
        <form method="GET" class="grid grid-cols-1 gap-3 border-b border-slate-100 p-4 sm:grid-cols-2 lg:grid-cols-6">
            <div class="relative lg:col-span-2">
                <span class="pointer-events-none absolute inset-y-0 left-3 grid place-items-center text-slate-400"><x-icon name="search" class="size-4" /></span>
                <input name="q" value="{{ request('q') }}" placeholder="Nomor booking, nama, NIP/NIK, email…" class="input pl-9">
            </div>
            <select name="building" class="input">
                <option value="">Semua gedung</option>
                @foreach ($buildings as $b)<option value="{{ $b->id }}" @selected(request('building') == $b->id)>{{ $b->name }}</option>@endforeach
            </select>
            <select name="room" class="input">
                <option value="">Semua ruangan</option>
                @foreach ($rooms as $r)<option value="{{ $r->id }}" @selected(request('room') == $r->id)>{{ $r->name }} ({{ $r->building?->code }})</option>@endforeach
            </select>
            <input type="date" name="date" value="{{ request('date') }}" class="input">
            <div class="flex gap-2">
                <select name="status" class="input">
                    <option value="">Semua status</option>
                    @foreach (\App\Enums\BookingStatus::cases() as $s)<option value="{{ $s->value }}" @selected(request('status') === $s->value)>{{ $s->label() }}</option>@endforeach
                </select>
                <button class="btn btn-secondary" title="Filter"><x-icon name="funnel" class="size-4" /></button>
            </div>
        </form>
        @if ($bookings->isEmpty())
            <x-empty message="Tidak ada data peminjaman." icon="clipboard-list" />
        @else
            <div class="overflow-x-auto">
                <table class="table">
                    <thead><tr><th>Booking Number</th><th>Nama</th><th>NIP/NIK</th><th>Gedung</th><th>Ruangan</th><th>Tanggal</th><th>Waktu</th><th>Status</th><th class="text-right">Aksi</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($bookings as $b)
                            <tr>
                                <td><a href="{{ route('admin.bookings.show', $b) }}" class="font-medium text-slate-900 hover:text-indigo-600">{{ $b->booking_number }}</a></td>
                                <td>{{ $b->user->name }}<div class="text-xs text-slate-500">{{ $b->user->user_type->label() }}</div></td>
                                <td class="font-mono text-xs">{{ $b->user->identifier() ?? '-' }}</td>
                                <td>{{ $b->room->building->name }}</td>
                                <td>{{ $b->room->name }}</td>
                                <td class="whitespace-nowrap">{{ $b->start_at->translatedFormat('d M Y') }}</td>
                                <td class="whitespace-nowrap">{{ $b->timeLabel() }}</td>
                                <td><x-status-badge :status="$b->status" /></td>
                                <td>
                                    <div class="flex justify-end gap-1">
                                        <a href="{{ route('admin.bookings.show', $b) }}" class="btn btn-ghost btn-sm" title="View"><x-icon name="eye" class="size-4" /></a>
                                        @can('approve', $b)
                                            <form method="POST" action="{{ route('admin.bookings.approve', $b) }}" onsubmit="return confirm('Setujui {{ $b->booking_number }}?')">
                                                @csrf
                                                <button class="btn btn-ghost btn-sm text-emerald-600 hover:bg-emerald-50" title="Approve"><x-icon name="check" class="size-4" /></button>
                                            </form>
                                            <a href="{{ route('admin.bookings.show', $b) }}#reject" class="btn btn-ghost btn-sm text-red-600 hover:bg-red-50" title="Reject"><x-icon name="x" class="size-4" /></a>
                                        @endcan
                                        @can('downloadProof', $b)
                                            <a href="{{ route('admin.bookings.pdf', $b) }}" class="btn btn-ghost btn-sm" title="Print"><x-icon name="printer" class="size-4" /></a>
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
