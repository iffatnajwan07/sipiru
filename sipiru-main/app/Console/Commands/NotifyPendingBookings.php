<?php

namespace App\Console\Commands;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Notifications\PendingBookingsDigest;
use App\Services\MailSettings;
use Illuminate\Console\Attributes\AsCommand;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

#[AsCommand(name: 'bookings:notify-pending', description: 'Kirim ringkasan pengajuan yang menunggu persetujuan ke admin')]
class NotifyPendingBookings extends Command
{
    protected $signature = 'bookings:notify-pending';

    public function handle(MailSettings $mail): int
    {
        $query = Booking::with('room.building')->status(BookingStatus::Pending)->where('start_at', '>', now())->orderBy('start_at');
        $total = (clone $query)->count();
        $recipients = $mail->adminRecipients();

        if ($total === 0 || $recipients === []) {
            $this->info('Tidak ada pengajuan menunggu / penerima admin.');

            return self::SUCCESS;
        }

        $items = $query->limit(10)->get()->map(fn (Booking $b) => [
            'number' => $b->booking_number,
            'room' => $b->room->name.' ('.$b->room->building->name.')',
            'when' => $b->start_at->translatedFormat('d M Y').' '.$b->timeLabel(),
        ])->all();

        Notification::route('mail', $recipients)->notify(new PendingBookingsDigest($total, $items));
        $this->info("Ringkasan {$total} pengajuan dikirim.");

        return self::SUCCESS;
    }
}
