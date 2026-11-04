<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Ke admin: ringkasan pengajuan yang masih menunggu persetujuan. */
class PendingBookingsDigest extends Notification implements ShouldQueue
{
    use Queueable;

    /** @param list<array{number:string, room:string, when:string}> $items */
    public function __construct(public int $total, public array $items) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject("Ada {$this->total} pengajuan peminjaman menunggu persetujuan")
            ->greeting('Pengajuan menunggu persetujuan')
            ->line("Terdapat **{$this->total}** pengajuan yang belum diproses. Pengajuan terdekat:");

        foreach ($this->items as $item) {
            $mail->line("• {$item['number']} — {$item['room']} — {$item['when']}");
        }

        return $mail->action('Buka Daftar Menunggu Persetujuan', route('admin.bookings.index', ['status' => 'pending']));
    }
}
