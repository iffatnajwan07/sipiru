@if ($booking->isApproved() && $booking->verificationUrl())
    <div class="card card-body text-center">
        <h3 class="mb-3 text-sm font-semibold">QR Bukti Peminjaman</h3>
        <div class="mx-auto w-44 text-slate-900 [&_svg]:h-auto [&_svg]:w-full">{!! \App\Support\QrCode::svg($booking->verificationUrl()) !!}</div>
        <p class="mt-2 text-xs text-slate-500">Scan untuk memverifikasi keabsahan peminjaman.</p>
        <div class="mt-4 grid gap-2">
            <a href="{{ $pdfUrl }}" class="btn btn-primary"><x-icon name="download" class="size-4" /> Download Bukti PDF</a>
            <a href="{{ $booking->googleCalendarUrl() }}" target="_blank" rel="noopener" class="btn btn-secondary"><x-icon name="calendar-plus" class="size-4" /> Add to Google Calendar</a>
        </div>
    </div>
@endif
