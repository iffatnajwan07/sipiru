<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\Building;
use App\Models\Room;
use App\Services\BookingService;
use App\Support\BookingPdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class BookingController extends Controller
{
    public function __construct(private BookingService $bookings) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Booking::class);
        $request->validate([
            'status' => ['nullable', Rule::in(BookingStatus::values())],
            'date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $bookings = Booking::query()
            ->with(['room.building', 'user'])
            ->when($request->q, function ($q, $term) {
                $like = '%'.addcslashes($term, '%_').'%';
                $q->where(fn ($w) => $w->where('booking_number', 'like', $like)
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', $like)
                        ->orWhere('email', 'like', $like)->orWhere('nip', 'like', $like)->orWhere('nik', 'like', $like)));
            })
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->room, fn ($q, $id) => $q->where('room_id', $id))
            ->when($request->building, fn ($q, $id) => $q->whereHas('room', fn ($r) => $r->where('building_id', $id)))
            ->when($request->date, fn ($q, $d) => $q->whereDate('start_at', $d))
            ->when($request->status === BookingStatus::Pending->value, fn ($q) => $q->orderBy('start_at'), fn ($q) => $q->latest('start_at'))
            ->paginate(15)
            ->withQueryString();

        return view('admin.bookings.index', [
            'bookings' => $bookings,
            'buildings' => Building::orderBy('name')->get(['id', 'name']),
            'rooms' => Room::with('building:id,code')->orderBy('name')->get(['id', 'name', 'building_id']),
        ]);
    }

    public function show(Booking $booking): View
    {
        $this->authorize('view', $booking);
        $booking->load('room.building', 'user', 'approver', 'canceller');

        return view('admin.bookings.show', [
            'booking' => $booking,
            'conflicts' => $booking->status === BookingStatus::Pending
                ? $this->bookings->conflicts($booking->room, $booking->start_at, $booking->end_at, true, $booking->id)->load('user')
                : collect(),
            'logs' => ActivityLog::with('user')->where('subject_type', $booking->getMorphClass())->where('subject_id', $booking->id)->oldest('id')->get(),
        ]);
    }

    public function approve(Request $request, Booking $booking): RedirectResponse
    {
        $this->authorize('approve', $booking);
        $this->bookings->approve($booking, $request->user());

        return back()->with('success', "Peminjaman {$booking->booking_number} disetujui. Notifikasi dikirim ke pemohon.");
    }

    public function reject(Request $request, Booking $booking): RedirectResponse
    {
        $this->authorize('reject', $booking);
        $data = $request->validate(['rejection_reason' => ['required', 'string', 'min:5', 'max:1000']], [], ['rejection_reason' => 'alasan penolakan']);
        $this->bookings->reject($booking, $request->user(), $data['rejection_reason']);

        return back()->with('success', "Peminjaman {$booking->booking_number} ditolak.");
    }

    public function cancel(Request $request, Booking $booking): RedirectResponse
    {
        $this->authorize('cancel', $booking);
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:500']], [], ['reason' => 'alasan pembatalan']);
        $this->bookings->cancel($booking, $request->user(), $data['reason']);

        return back()->with('success', "Peminjaman {$booking->booking_number} dibatalkan.");
    }

    public function pdf(Booking $booking): Response
    {
        $this->authorize('downloadProof', $booking);

        return BookingPdf::download($booking);
    }
}
