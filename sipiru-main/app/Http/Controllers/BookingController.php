<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Http\Requests\BookingRequest;
use App\Models\Booking;
use App\Models\Building;
use App\Models\Period;
use App\Models\Room;
use App\Services\BookingService;
use App\Support\BookingPdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class BookingController extends Controller
{
    public function __construct(private BookingService $bookings) {}

    /** Riwayat: HANYA booking milik user yang login. */
    public function index(Request $request): View
    {
        $request->validate(['status' => ['nullable', Rule::in(BookingStatus::values())]]);

        $bookings = $request->user()->bookings()
            ->with('room.building')
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->q, fn ($q, $term) => $q->where('booking_number', 'like', '%'.addcslashes($term, '%_').'%'))
            ->latest('start_at')
            ->paginate(10)
            ->withQueryString();

        return view('bookings.index', compact('bookings'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Booking::class);

        $selectedRoom = $request->integer('room')
            ? Room::with('building')->bookable()->find($request->integer('room'))
            : null;

        return view('bookings.create', [
            'buildings' => Building::active()->whereHas('rooms', fn ($q) => $q->where('is_active', true))->orderBy('name')->get(['id', 'name', 'code']),
            'selectedRoom' => $selectedRoom,
            'period' => Period::current(),
        ]);
    }

    public function rooms(Building $building): JsonResponse
{
    abort_unless($building->is_active, 404);

    return response()->json(
        $building->rooms()->where('is_active', true)->orderBy('name')
            ->get(['id', 'code', 'name', 'floor', 'capacity', 'price'])
    );
}

    public function availability(BookingRequest $request): JsonResponse
    {
        $room = Room::with('building')->findOrFail($request->validated('room_id'));

        return response()->json($this->bookings->availability($room, $request->startAt(), $request->endAt()));
    }

    public function store(BookingRequest $request): RedirectResponse
    {
        $room = Room::with('building')->findOrFail($request->validated('room_id'));
        
        $activityType = $request->validated('activity_type');
        // Jika USK maka harga 0, jika Non-USK ambil dari harga ruangan
        $finalPrice = ($activityType === 'usk') ? 0 : ($room->price ?? 0);

        // Sesuaikan pemanggilan service atau buat langsung jika menggunakan model create
        $booking = $this->bookings->create(
            $request->user(), 
            $room, 
            $request->startAt(), 
            $request->endAt(), 
            $request->validated('notes'),
            $activityType,
            $finalPrice
        );

        return redirect()->route('bookings.show', $booking)
            ->with('success', "Pengajuan {$booking->booking_number} berhasil dikirim dan menunggu persetujuan admin.");
    }

    public function show(Booking $booking): View
    {
        $this->authorize('view', $booking);
        $booking->load('room.building', 'approver');

        return view('bookings.show', compact('booking'));
    }

    public function cancel(Request $request, Booking $booking): RedirectResponse
    {
        $this->authorize('cancel', $booking);
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:500']]);

        $this->bookings->cancel($booking, $request->user(), $data['reason'] ?? null);

        return back()->with('success', "Peminjaman {$booking->booking_number} dibatalkan.");
    }

    public function pdf(Booking $booking): Response
    {
        $this->authorize('downloadProof', $booking);

        return BookingPdf::download($booking);
    }
}