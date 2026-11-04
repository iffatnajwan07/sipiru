<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

class BookingRejected extends BookingNotification
{
    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Peminjaman Ditolak — '.$this->booking->booking_number)
            ->greeting('Halo, '.$notifiable->name)
            ->line('Mohon maaf, pengajuan peminjaman ruangan Anda **DITOLAK**.');

        return $this->details($mail)
            ->line('**Alasan:** '.($this->booking->rejection_reason ?: '-'))
            ->action('Lihat Detail', route('bookings.show', $this->booking));
    }
}
