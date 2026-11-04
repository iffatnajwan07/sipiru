<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

/** Ke admin: ada pengajuan baru. */
class BookingSubmitted extends BookingNotification
{
    public function toMail(object $notifiable): MailMessage
    {
        $b = $this->booking->loadMissing('user');

        $mail = (new MailMessage)
            ->subject('Pengajuan Peminjaman Baru — '.$b->booking_number)
            ->greeting('Pengajuan peminjaman baru')
            ->line('**Pemohon:** '.$b->user->name.' ('.$b->user->user_type->label().')');

        return $this->details($mail)
            ->when($b->notes, fn ($m) => $m->line('**Catatan:** '.$b->notes))
            ->action('Tinjau Pengajuan', route('admin.bookings.show', $b));
    }
}
