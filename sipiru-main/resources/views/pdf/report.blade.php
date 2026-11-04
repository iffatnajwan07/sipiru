<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Peminjaman Ruang</title>
    <style>
        @page { margin: 14mm; }
        body { font-family: DejaVu Sans, sans-serif; color: #0f172a; font-size: 8.5pt; }
        h1 { font-size: 14pt; margin: 0; }
        .muted { color: #64748b; }
        .summary td { border: 1px solid #e2e8f0; padding: 6px 10px; text-align: center; }
        .summary .n { font-size: 13pt; font-weight: bold; }
        table.data { width: 100%; border-collapse: collapse; margin-top: 8px; }
        table.data th { background: #f1f5f9; text-align: left; padding: 5px; border-bottom: 1px solid #cbd5e1; }
        table.data td { padding: 4px 5px; border-bottom: 1px solid #e2e8f0; }
        h2 { font-size: 10.5pt; margin: 16px 0 4px; }
    </style>
</head>
<body>
    <h1>Laporan Peminjaman Ruang — Universitas Syiah Kuala</h1>
    <div class="muted">Dicetak {{ now()->translatedFormat('d F Y H:i') }} WIB
        @if ($filterLabels) · Filter: @foreach ($filterLabels as $k => $v){{ $k }}: {{ $v }}@if(! $loop->last), @endif @endforeach @endif
    </div>

    <table class="summary" style="margin-top: 10px; border-collapse: collapse;">
        <tr>
            <td><div class="n">{{ $summary['total'] }}</div>Jumlah</td>
            <td><div class="n">{{ $summary['approved'] }}</div>Approved/Selesai</td>
            <td><div class="n">{{ $summary['pending'] }}</div>Menunggu</td>
            <td><div class="n">{{ $summary['rejected'] }}</div>Rejected</td>
            <td><div class="n">{{ $summary['cancelled'] }}</div>Cancelled</td>
        </tr>
    </table>

    <h2>Pemakaian Ruangan</h2>
    <table class="data">
        <thead><tr><th>Ruangan</th><th>Kode</th><th>Gedung</th><th>Jumlah</th><th>Total Jam</th></tr></thead>
        <tbody>
            @forelse ($usage as $row)
                <tr><td>{{ $row['room'] }}</td><td>{{ $row['code'] }}</td><td>{{ $row['building'] }}</td><td>{{ $row['count'] }}</td><td>{{ $row['hours'] }}</td></tr>
            @empty
                <tr><td colspan="5" class="muted">Tidak ada data.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Detail Peminjaman</h2>
    <table class="data">
        <thead><tr><th>Nomor</th><th>Pemohon</th><th>NIP/NIK</th><th>Gedung</th><th>Ruangan</th><th>Tanggal</th><th>Waktu</th><th>Status</th></tr></thead>
        <tbody>
            @forelse ($bookings as $b)
                <tr>
                    <td>{{ $b->booking_number }}</td><td>{{ $b->user->name }}</td><td>{{ $b->user->identifier() }}</td>
                    <td>{{ $b->room->building->name }}</td><td>{{ $b->room->name }}</td>
                    <td>{{ $b->start_at->format('d/m/Y') }}</td><td>{{ $b->timeLabel() }}</td><td>{{ $b->status->label() }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="muted">Tidak ada data.</td></tr>
            @endforelse
        </tbody>
    </table>
    @if ($bookings->count() >= 1000)<p class="muted">Ditampilkan maksimal 1000 baris. Gunakan export CSV untuk data lengkap.</p>@endif
</body>
</html>
