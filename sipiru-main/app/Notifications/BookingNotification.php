<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

abstract class BookingNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public Booking $booking)
    {
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /** Ringkasan detail peminjaman (tanpa data sensitif). */
    protected function details(MailMessage $mail): MailMessage
    {
        $b = $this->booking->loadMissing('room.building', 'user');

        return $mail
            ->line('**Nomor:** '.$b->booking_number)
            ->line('**Gedung:** '.$b->room->building->name)
            ->line('**Ruangan:** '.$b->room->name.' ('.$b->room->code.')')
            ->line('**Tanggal:** '.$b->dateLabel())
            ->line('**Waktu:** '.$b->timeLabel().' WIB');
    }
}
