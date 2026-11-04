<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        // Semua query dibatasi pada booking milik user yang login
        $counts = $user->bookings()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('dashboard', [
            'stats' => [
                'total' => $counts->sum(),
                'approved' => (int) ($counts[BookingStatus::Approved->value] ?? 0) + (int) ($counts[BookingStatus::Completed->value] ?? 0),
                'pending' => (int) ($counts[BookingStatus::Pending->value] ?? 0),
                'rejected' => (int) ($counts[BookingStatus::Rejected->value] ?? 0),
            ],
            'upcoming' => $user->upcomingBookings()->with('room.building')->limit(5)->get(),
            'recent' => $user->bookings()->with('room.building')->latest()->limit(5)->get(),
        ]);
    }
}
