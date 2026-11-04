{{-- Detail booking (dipakai user & admin) --}}
<dl class="divide-y divide-slate-100">
    <div class="dl-row"><dt>Nomor</dt><dd>{{ $booking->booking_number }}</dd></div>
    @if ($showApplicant ?? false)
        <div class="dl-row"><dt>Pemohon</dt><dd><a href="{{ route('admin.users.show', $booking->user) }}" class="text-indigo-600 hover:underline">{{ $booking->user->name }}</a> <span class="font-normal text-slate-500">· {{ $booking->user->user_type->label() }}</span></dd></div>
        <div class="dl-row"><dt>{{ $booking->user->identifierLabel() }}</dt><dd>{{ $booking->user->identifier() ?? '-' }}</dd></div>
        <div class="dl-row"><dt>Kontak</dt><dd>{{ $booking->user->email }}@if($booking->user->phone) · {{ $booking->user->phone }}@endif</dd></div>
    @endif
    <div class="dl-row"><dt>Gedung</dt><dd>{{ $booking->room->building->name }}</dd></div>
    <div class="dl-row"><dt>Ruangan</dt><dd>{{ $booking->room->name }} <span class="font-normal text-slate-500">({{ $booking->room->code }})</span></dd></div>
    <div class="dl-row"><dt>Tanggal</dt><dd>{{ $booking->dateLabel() }}</dd></div>
    <div class="dl-row"><dt>Waktu</dt><dd>{{ $booking->timeLabel() }} WIB</dd></div>
    <div class="dl-row"><dt>Status</dt><dd><x-status-badge :status="$booking->status" /></dd></div>
    <div class="dl-row"><dt>Catatan</dt><dd class="font-normal whitespace-pre-line">{{ $booking->notes ?: '-' }}</dd></div>
    <div class="dl-row"><dt>Diajukan</dt><dd class="font-normal">{{ $booking->created_at->translatedFormat('d F Y H:i') }}</dd></div>
    @if ($booking->approved_at)
        <div class="dl-row"><dt>{{ $booking->status === \App\Enums\BookingStatus::Rejected ? 'Ditolak' : 'Disetujui' }}</dt><dd class="font-normal">{{ $booking->approved_at->translatedFormat('d F Y H:i') }}@if($booking->approver) oleh {{ $booking->approver->name }}@endif</dd></div>
    @endif
    @if ($booking->rejection_reason)
        <div class="dl-row"><dt>Alasan penolakan</dt><dd class="font-normal text-red-700">{{ $booking->rejection_reason }}</dd></div>
    @endif
    @if ($booking->cancelled_at)
        <div class="dl-row"><dt>Dibatalkan</dt><dd class="font-normal">{{ $booking->cancelled_at->translatedFormat('d F Y H:i') }}@if($booking->cancellation_reason) — {{ $booking->cancellation_reason }}@endif</dd></div>
    @endif
</dl>
