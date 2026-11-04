<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Bukti Peminjaman {{ $booking->booking_number }}</title>
    <style>
        @page { margin: 28mm 22mm; }
        body { font-family: DejaVu Sans, sans-serif; color: #0f172a; font-size: 11pt; }
        .header { border-bottom: 2px solid #4f46e5; padding-bottom: 10px; margin-bottom: 18px; }
        .uni { font-size: 9pt; letter-spacing: 2px; color: #4f46e5; font-weight: bold; }
        h1 { font-size: 17pt; margin: 4px 0 0; }
        .number { font-size: 12pt; color: #475569; margin-top: 2px; }
        table.info { width: 100%; border-collapse: collapse; }
        table.info td { padding: 7px 0; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
        table.info td.k { width: 34%; color: #64748b; }
        table.info td.v { font-weight: bold; }
        .status { display: inline-block; background: #dcfce7; color: #166534; padding: 3px 10px; border-radius: 10px; font-size: 10pt; }
        .qr { text-align: center; margin-top: 24px; }
        .qr img { width: 150px; height: 150px; }
        .muted { color: #64748b; font-size: 9pt; }
        .footer { position: fixed; bottom: -12mm; left: 0; right: 0; font-size: 8pt; color: #94a3b8; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <div class="uni">UNIVERSITAS SYIAH KUALA</div>
        <h1>PEMINJAMAN RUANG USK</h1>
        <div class="number">Nomor: {{ $booking->booking_number }}</div>
    </div>

    <table class="info">
        <tr><td class="k">Pemohon</td><td class="v">{{ $booking->user->name }}</td></tr>
        <tr><td class="k">{{ $booking->user->identifierLabel() }}</td><td class="v">{{ $booking->user->identifier() ?? '-' }}</td></tr>
        <tr><td class="k">Gedung</td><td class="v">{{ $booking->room->building->name }}</td></tr>
        <tr><td class="k">Ruangan</td><td class="v">{{ $booking->room->name }} ({{ $booking->room->code }})</td></tr>
        <tr><td class="k">Tanggal</td><td class="v">{{ $booking->dateLabel() }}</td></tr>
        <tr><td class="k">Waktu</td><td class="v">{{ $booking->timeLabel() }} WIB</td></tr>
        @if ($booking->notes)<tr><td class="k">Keperluan</td><td>{{ $booking->notes }}</td></tr>@endif
        <tr><td class="k">Status</td><td><span class="status">{{ mb_strtoupper($booking->status->label()) }}</span></td></tr>
        <tr><td class="k">Tanggal persetujuan</td><td class="v">{{ $booking->approved_at?->translatedFormat('d F Y H:i') }} WIB</td></tr>
        @if ($booking->approver)<tr><td class="k">Disetujui oleh</td><td>{{ $booking->approver->name }}</td></tr>@endif
    </table>

    @if ($qr)
        <div class="qr">
            <img src="{{ $qr }}" alt="QR Code">
            <div class="muted">Scan QR Code untuk memverifikasi keabsahan bukti peminjaman ini.</div>
            <div class="muted">{{ $booking->verificationUrl() }}</div>
        </div>
    @endif

    <div class="footer">Dokumen dibuat otomatis oleh {{ config('app.name') }} pada {{ now()->translatedFormat('d F Y H:i') }} WIB.</div>
</body>
</html>
