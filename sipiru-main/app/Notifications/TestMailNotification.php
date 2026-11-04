<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Email uji SMTP — dikirim sinkron (tidak lewat antrean) agar hasilnya langsung terlihat. */
class TestMailNotification extends Notification
{
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Test SMTP — '.config('app.name'))
            ->greeting('Konfigurasi SMTP berhasil')
            ->line('Email ini dikirim dari halaman Admin → Settings → Email untuk menguji konfigurasi SMTP.')
            ->line('Waktu: '.now()->translatedFormat('d F Y H:i:s').' WIB');
    }
}
