<?php

namespace App\Support;

use App\Models\Booking;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

final class BookingPdf
{
    public static function download(Booking $booking): Response
    {
        $booking->loadMissing('room.building', 'user', 'approver');

        return Pdf::loadView('pdf.booking-proof', [
            'booking' => $booking,
            'qr' => $booking->verificationUrl() ? QrCode::pngDataUri($booking->verificationUrl(), 6) : null,
        ])->setPaper('a4')->download("bukti-{$booking->booking_number}.pdf");
    }
}
