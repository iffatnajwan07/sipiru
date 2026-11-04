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
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.dashboard', [
            'stats' => [
                'buildings' => Building::count(),
                'rooms' => Room::count(),
                'pending' => Booking::status(BookingStatus::Pending)->count(),
                'today' => Booking::status(BookingStatus::Approved, BookingStatus::Completed)->whereDate('start_at', today())->count(),
                'approved' => Booking::status(BookingStatus::Approved)->count(),
                'users' => User::where('user_type', '!=', UserType::Admin->value)->count(),
            ],
            'period' => Period::current(),
            'pendingBookings' => Booking::with('room.building', 'user')->status(BookingStatus::Pending)->orderBy('start_at')->limit(6)->get(),
            'todayBookings' => Booking::with('room.building', 'user')->status(BookingStatus::Approved, BookingStatus::Completed)
                ->whereDate('start_at', today())->orderBy('start_at')->get(),
        ]);
    }
}
