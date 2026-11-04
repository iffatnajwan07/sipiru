<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Building;
use App\Models\Period;
use App\Models\Room;
use App\Models\User;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(private ReportService $reports) {}

    public function index(Request $request): View
    {
        $filters = $this->filters($request);

        return view('admin.reports.index', [
            'filters' => $filters,
            'summary' => $this->reports->summary($filters),
            'usage' => $this->reports->roomUsage($filters),
            'bookings' => $this->reports->query($filters)->latest('start_at')->paginate(20)->withQueryString(),
            'periods' => Period::orderByDesc('year')->get(),
            'buildings' => Building::orderBy('name')->get(['id', 'name']),
            'rooms' => Room::with('building:id,code')->orderBy('name')->get(['id', 'name', 'building_id']),
            'users' => User::where('user_type', '!=', UserType::Admin->value)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function csv(Request $request): StreamedResponse
    {
        $filters = $this->filters($request);
        $query = $this->reports->query($filters)->oldest('start_at');

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM agar Excel membaca UTF-8
            fputcsv($out, ['Nomor', 'Pemohon', 'Jenis', 'NIP/NIK', 'Gedung', 'Ruangan', 'Tanggal', 'Mulai', 'Selesai', 'Status', 'Catatan']);
            $query->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $b) {
                    /** @var Booking $b */
                    fputcsv($out, array_map([$this, 'csvSafe'], [
                        $b->booking_number, $b->user->name, $b->user->user_type->label(), $b->user->identifier(),
                        $b->room->building->name, $b->room->name, $b->start_at->format('Y-m-d'),
                        $b->start_at->format('H:i'), $b->end_at->format('H:i'), $b->status->label(), $b->notes,
                    ]));
                }
            });
            fclose($out);
        }, 'laporan-peminjaman-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function pdf(Request $request): Response
    {
        $filters = $this->filters($request);

        return Pdf::loadView('pdf.report', [
            'filters' => $filters,
            'filterLabels' => $this->filterLabels($filters),
            'summary' => $this->reports->summary($filters),
            'usage' => $this->reports->roomUsage($filters),
            'bookings' => $this->reports->query($filters)->oldest('start_at')->limit(1000)->get(),
        ])->setPaper('a4', 'landscape')->download('laporan-peminjaman-'.now()->format('Ymd-His').'.pdf');
    }

    /** Cegah CSV/formula injection saat dibuka di Excel. */
    public function csvSafe(mixed $value): string
    {
        $value = (string) $value;

        return preg_match('/^[=+\-@\t\r]/', $value) ? "'".$value : $value;
    }

    private function filters(Request $request): array
    {
        $this->authorize('viewAny', Booking::class);

        return $request->validate([
            'period' => ['nullable', 'integer'],
            'building' => ['nullable', 'integer'],
            'room' => ['nullable', 'integer'],
            'status' => ['nullable', Rule::in(BookingStatus::values())],
            'user' => ['nullable', 'integer'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);
    }

    private function filterLabels(array $f): array
    {
        return array_filter([
            'Periode' => isset($f['period']) ? Period::find($f['period'])?->year : null,
            'Gedung' => isset($f['building']) ? Building::withTrashed()->find($f['building'])?->name : null,
            'Ruangan' => isset($f['room']) ? Room::withTrashed()->find($f['room'])?->name : null,
            'Status' => isset($f['status']) ? BookingStatus::from($f['status'])->label() : null,
            'User' => isset($f['user']) ? User::withTrashed()->find($f['user'])?->name : null,
            'Dari' => $f['from'] ?? null,
            'Sampai' => $f['to'] ?? null,
        ]);
    }
}
