<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

class BookingCancelled extends BookingNotification
{
    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Peminjaman Dibatalkan — '.$this->booking->booking_number)
            ->greeting('Halo, '.$notifiable->name)
            ->line('Peminjaman ruangan Anda telah **DIBATALKAN** oleh admin.');

        return $this->details($mail)
            ->when($this->booking->cancellation_reason, fn ($m) => $m->line('**Alasan:** '.$this->booking->cancellation_reason))
            ->action('Lihat Detail', route('bookings.show', $this->booking));
    }
}
