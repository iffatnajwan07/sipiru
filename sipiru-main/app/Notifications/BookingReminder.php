<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Notifications\Messages\MailMessage;

/** Pengingat ke user: H-1 (day) atau mendekati waktu peminjaman (soon). */
class BookingReminder extends BookingNotification
{
    public function __construct(Booking $booking, public string $type = 'day')
    {
        parent::__construct($booking);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $soon = $this->type === 'soon';

        $mail = (new MailMessage)
            ->subject(($soon ? 'Segera Dimulai' : 'Pengingat Peminjaman').' — '.$this->booking->booking_number)
            ->greeting('Halo, '.$notifiable->name)
            ->line($soon
                ? 'Peminjaman ruangan Anda akan segera dimulai.'
                : 'Ini adalah pengingat untuk peminjaman ruangan Anda besok.');

        return $this->details($mail)
            ->action('Lihat Bukti Peminjaman', route('bookings.show', $this->booking))
            ->line('Jika tidak jadi menggunakan ruangan, mohon batalkan peminjaman agar dapat digunakan pihak lain.');
    }
}
