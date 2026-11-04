<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

class BookingApproved extends BookingNotification
{
    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Peminjaman Disetujui — '.$this->booking->booking_number)
            ->greeting('Halo, '.$notifiable->name)
            ->line('Pengajuan peminjaman ruangan Anda telah **DISETUJUI**.');

        return $this->details($mail)
            ->action('Unduh Bukti Peminjaman', route('bookings.show', $this->booking))
            ->line('Tunjukkan bukti peminjaman (PDF/QR Code) kepada petugas bila diperlukan.');
    }
}
