<x-layouts.app :title="$booking->booking_number">
    <x-page-header :title="$booking->booking_number" subtitle="Detail peminjaman ruangan" :back="route('bookings.index')" />

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="card lg:col-span-2">
            <div class="card-body">@include('bookings._details')</div>
            @can('cancel', $booking)
                <div class="border-t border-slate-100 px-5 py-4" x-data="{ open: false }">
                    <button type="button" class="btn btn-secondary text-red-600" @click="open = !open"><x-icon name="ban" class="size-4" /> Batalkan Peminjaman</button>
                    <form x-cloak x-show="open" method="POST" action="{{ route('bookings.cancel', $booking) }}" class="mt-4 space-y-3">
                        @csrf
                        <label for="reason" class="label">Alasan pembatalan (opsional)</label>
                        <textarea id="reason" name="reason" rows="2" maxlength="500" class="input"></textarea>
                        <button class="btn btn-danger">Konfirmasi Pembatalan</button>
                    </form>
                </div>
            @endcan
        </div>
        <div class="space-y-4">
            @include('bookings._proof-card', ['pdfUrl' => route('bookings.pdf', $booking)])
            @if ($booking->status === \App\Enums\BookingStatus::Pending)
                <div class="card card-body flex items-start gap-3 text-sm text-amber-800">
                    <x-icon name="hourglass" class="mt-0.5 size-5 text-amber-500" />
                    <p>Pengajuan sedang menunggu persetujuan admin. Anda akan menerima email setelah diproses.</p>
                </div>
            @endif
        </div>
    </div>
</x-layouts.app>
