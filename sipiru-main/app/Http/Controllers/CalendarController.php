<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Building;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Kalender bulanan.
 *  - Admin        : seluruh peminjaman lengkap dengan pemohon.
 *  - User / Guest : ketersediaan ruangan (slot terpakai) tanpa identitas peminjam lain.
 */
class CalendarController extends Controller
{
    public function __invoke(Request $request): View
    {
        $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'building' => ['nullable', 'integer'],
            'room' => ['nullable', 'integer'],
        ]);

        $user = $request->user();
        $isAdmin = $user?->isAdmin() ?? false;
        $userId = $user?->id;

        $month = $request->month ? Carbon::createFromFormat('Y-m-d', $request->month.'-01')->startOfDay() : now()->startOfMonth();
        $gridStart = $month->copy()->startOfWeek(Carbon::MONDAY);
        $gridEnd = $month->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

        $statuses = [BookingStatus::Approved, BookingStatus::Pending, BookingStatus::Completed];

        $bookings = Booking::query()
            ->with(['room.building', 'user:id,name'])
            ->status(...$statuses)
            ->where('start_at', '<=', $gridEnd->copy()->endOfDay())
            ->where('end_at', '>=', $gridStart)
            ->when($request->room, fn ($q, $id) => $q->where('room_id', $id))
            ->when($request->building, fn ($q, $id) => $q->whereHas('room', fn ($r) => $r->where('building_id', $id)))
            ->orderBy('start_at')
            ->get()
            ->map(fn (Booking $b) => [
                'date' => $b->start_at->toDateString(),
                'time' => $b->timeLabel(),
                'room' => $b->room->name,
                'building' => $b->room->building->name,
                'status' => $b->status,
                'mine' => $userId ? $b->user_id === $userId : false,
                // Identitas & nomor hanya untuk admin atau pemilik booking
                'title' => ($isAdmin || ($userId && $b->user_id === $userId)) ? $b->user?->name : null,
                'number' => ($isAdmin || ($userId && $b->user_id === $userId)) ? $b->booking_number : null,
                'url' => $isAdmin ? route('admin.bookings.show', $b) : (($userId && $b->user_id === $userId) ? route('bookings.show', $b) : null),
            ])
            ->groupBy('date');

        return view('calendar.index', [
            'isAdmin' => $isAdmin,
            'month' => $month,
            'gridStart' => $gridStart,
            'gridEnd' => $gridEnd,
            'bookings' => $bookings,
            'buildings' => Building::orderBy('name')->get(['id', 'name']),
            'rooms' => Room::when($request->building, fn ($q, $id) => $q->where('building_id', $id))->orderBy('name')->get(['id', 'name', 'building_id']),
            'routeName' => $isAdmin ? 'admin.calendar' : 'calendar',
        ]);
    }
}