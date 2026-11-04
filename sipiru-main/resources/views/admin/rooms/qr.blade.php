<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>QR {{ $room->code }} · {{ config('app.name') }}</title>
    @fonts
    @vite(['resources/css/app.css'])
    <style>@media print { .no-print { display: none !important; } body { background: #fff; } }</style>
</head>
<body class="bg-slate-100">
    <div class="no-print flex justify-center gap-2 p-4">
        <a href="{{ route('admin.rooms.show', $room) }}" class="btn btn-secondary"><x-icon name="arrow-left" class="size-4" /> Kembali</a>
        <button onclick="window.print()" class="btn btn-primary"><x-icon name="printer" class="size-4" /> Cetak</button>
    </div>
    <div class="mx-auto my-4 w-[148mm] rounded-2xl border border-slate-200 bg-white p-10 text-center shadow-sm print:my-0 print:border-0 print:shadow-none">
        <div class="text-xs font-semibold uppercase tracking-[0.2em] text-indigo-600">Universitas Syiah Kuala</div>
        <div class="mt-4 text-lg font-medium text-slate-500">{{ $room->building->name }}</div>
        <div class="text-3xl font-bold text-slate-900">{{ $room->name }}</div>
        <div class="mt-1 font-mono text-sm text-slate-500">{{ $room->code }}@if($room->floor) · Lantai {{ $room->floor }}@endif</div>
        <div class="mx-auto my-8 w-64 [&_svg]:h-auto [&_svg]:w-full">{!! $qrSvg !!}</div>
        <div class="text-base font-semibold text-slate-800">Scan untuk mengajukan peminjaman ruangan</div>
        <div class="mt-1 text-xs text-slate-400">{{ config('app.name') }}</div>
    </div>
</body>
</html>
