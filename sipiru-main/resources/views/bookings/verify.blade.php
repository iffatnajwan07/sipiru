<x-layouts.guest title="Verifikasi Peminjaman">
    @php
        $states = [
            'valid' => ['badge-check', 'bg-emerald-50 text-emerald-700 border-emerald-200', 'Booking Valid', 'Peminjaman ini sah dan telah disetujui.'],
            'expired' => ['clock', 'bg-slate-50 text-slate-700 border-slate-200', 'Peminjaman Telah Berakhir', 'Peminjaman ini pernah disetujui, namun waktunya sudah lewat.'],
            'revoked' => ['ban', 'bg-red-50 text-red-700 border-red-200', 'Booking Tidak Berlaku', 'Peminjaman ini telah dibatalkan atau tidak disetujui.'],
            'invalid' => ['circle-x', 'bg-red-50 text-red-700 border-red-200', 'QR Tidak Valid', 'Kode verifikasi tidak ditemukan.'],
        ];
        [$icon, $classes, $heading, $text] = $states[$state];
    @endphp
    <div class="card overflow-hidden">
        <div class="flex flex-col items-center border-b px-6 py-8 text-center {{ $classes }}">
            <x-icon :name="$icon" class="size-12" />
            <h1 class="mt-3 text-xl font-bold text-inherit">{{ $heading }}</h1>
            <p class="mt-1 text-sm opacity-80">{{ $text }}</p>
        </div>
        @if ($booking)
            {{-- Hanya data non-sensitif --}}
            <dl class="divide-y divide-slate-100 px-6 py-2">
                <div class="dl-row"><dt>Nomor Booking</dt><dd>{{ $booking->booking_number }}</dd></div>
                <div class="dl-row"><dt>Ruangan</dt><dd>{{ $booking->room->name }}</dd></div>
                <div class="dl-row"><dt>Gedung</dt><dd>{{ $booking->room->building->name }}</dd></div>
                <div class="dl-row"><dt>Tanggal</dt><dd>{{ $booking->dateLabel() }}</dd></div>
                <div class="dl-row"><dt>Waktu</dt><dd>{{ $booking->timeLabel() }} WIB</dd></div>
                <div class="dl-row"><dt>Status</dt><dd><x-status-badge :status="$booking->status" /></dd></div>
            </dl>
        @endif
    </div>
</x-layouts.guest>
