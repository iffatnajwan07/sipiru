<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Illuminate\Http\Response;

/** Halaman publik hasil scan QR bukti peminjaman — tanpa data sensitif. */
class BookingVerificationController extends Controller
{
    public function __invoke(string $token): Response
    {
        $booking = Booking::with('room.building')->where('verification_token', $token)->first();

        $state = match (true) {
            $booking === null => 'invalid',
            $booking->status === BookingStatus::Approved && $booking->end_at->isFuture() => 'valid',
            $booking->status === BookingStatus::Approved, $booking->status === BookingStatus::Completed => 'expired',
            default => 'revoked', // dibatalkan / ditolak
        };

        return response()->view('bookings.verify', compact('booking', 'state'), $state === 'invalid' ? 404 : 200);
    }
}
