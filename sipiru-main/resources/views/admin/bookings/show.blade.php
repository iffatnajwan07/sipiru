<x-layouts.app :title="$booking->booking_number">
    <x-page-header :title="$booking->booking_number" subtitle="Detail & persetujuan peminjaman" :back="route('admin.bookings.index')" />

    @if ($conflicts->isNotEmpty())
        <div class="mb-5 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            <div class="flex items-center gap-2 font-semibold"><x-icon name="triangle-alert" class="size-4" /> Terdapat jadwal yang bentrok pada ruangan ini:</div>
            <ul class="mt-2 space-y-1 pl-6">
                @foreach ($conflicts as $c)
                    <li><a href="{{ route('admin.bookings.show', $c) }}" class="font-medium underline">{{ $c->booking_number }}</a> · {{ $c->timeLabel() }} · {{ $c->user->name }} · {{ $c->status->label() }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <div class="card">
                <div class="card-body">@include('bookings._details', ['showApplicant' => true])</div>
            </div>
            <div class="card">
                <div class="card-header"><h2 class="card-title">Riwayat</h2></div>
                @include('admin._activity-list')
            </div>
        </div>

        <div class="space-y-4">
            @can('approve', $booking)
                <div class="card card-body space-y-4" x-data="{ reject: window.location.hash === '#reject' || @js($errors->has('rejection_reason')) }">
                    <h3 class="text-sm font-semibold">Persetujuan</h3>
                    <form method="POST" action="{{ route('admin.bookings.approve', $booking) }}" x-show="!reject">
                        @csrf
                        <button class="btn btn-success w-full"><x-icon name="check" class="size-4" /> Approve</button>
                    </form>
                    <button type="button" x-show="!reject" @click="reject = true" class="btn btn-secondary w-full text-red-600"><x-icon name="x" class="size-4" /> Reject</button>
                    <form x-cloak x-show="reject" method="POST" action="{{ route('admin.bookings.reject', $booking) }}" class="space-y-3" id="reject">
                        @csrf
                        <label for="rejection_reason" class="label">Alasan penolakan</label>
                        <textarea id="rejection_reason" name="rejection_reason" rows="3" required minlength="5" maxlength="1000" class="input @error('rejection_reason') input-error @enderror">{{ old('rejection_reason') }}</textarea>
                        <x-field-error name="rejection_reason" />
                        <div class="flex gap-2">
                            <button type="button" class="btn btn-secondary" @click="reject = false">Batal</button>
                            <button class="btn btn-danger flex-1">Tolak Pengajuan</button>
                        </div>
                    </form>
                </div>
            @endcan

            @include('bookings._proof-card', ['pdfUrl' => route('admin.bookings.pdf', $booking)])

            @can('cancel', $booking)
                <div class="card card-body" x-data="{ open: @js($errors->has('reason')) }">
                    <button type="button" class="btn btn-secondary w-full text-red-600" @click="open = !open"><x-icon name="ban" class="size-4" /> Cancel Peminjaman</button>
                    <form x-cloak x-show="open" method="POST" action="{{ route('admin.bookings.cancel', $booking) }}" class="mt-3 space-y-3">
                        @csrf
                        <label for="reason" class="label">Alasan pembatalan</label>
                        <textarea id="reason" name="reason" rows="2" required minlength="5" maxlength="500" class="input">{{ old('reason') }}</textarea>
                        <x-field-error name="reason" />
                        <button class="btn btn-danger w-full">Konfirmasi Pembatalan</button>
                    </form>
                </div>
            @endcan
        </div>
    </div>
</x-layouts.app>
