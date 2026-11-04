<?php

namespace App\Console\Commands;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Notifications\BookingReminder;
use Illuminate\Console\Attributes\AsCommand;
use Illuminate\Console\Command;
use Throwable;

#[AsCommand(name: 'bookings:send-reminders', description: 'Kirim pengingat H-1 dan menjelang waktu peminjaman')]
class SendBookingReminders extends Command
{
    protected $signature = 'bookings:send-reminders';

    public function handle(): int
    {
        $sent = 0;

        // Pengingat H-1 (dalam 24 jam ke depan, belum pernah dikirim)
        $dayHours = (int) config('booking.reminder_day_before_hours');
        $soonMinutes = (int) config('booking.reminder_soon_minutes');

        $sent += $this->remind(
            Booking::status(BookingStatus::Approved)->whereNull('reminder_day_sent_at')
                ->where('start_at', '>', now()->addMinutes($soonMinutes))
                ->where('start_at', '<=', now()->addHours($dayHours)),
            'day', 'reminder_day_sent_at',
        );

        // Pengingat menjelang waktu (≤ 60 menit)
        $sent += $this->remind(
            Booking::status(BookingStatus::Approved)->whereNull('reminder_soon_sent_at')
                ->where('start_at', '>', now())
                ->where('start_at', '<=', now()->addMinutes($soonMinutes)),
            'soon', 'reminder_soon_sent_at',
        );

        $this->info("Pengingat dikirim: {$sent}");

        return self::SUCCESS;
    }

    private function remind($query, string $type, string $column): int
    {
        $count = 0;
        $query->with('user', 'room.building')->chunkById(100, function ($bookings) use ($type, $column, &$count) {
            foreach ($bookings as $booking) {
                try {
                    $booking->user->notify(new BookingReminder($booking, $type));
                    $booking->forceFill([$column => now()])->saveQuietly();
                    $count++;
                } catch (Throwable $e) {
                    report($e);
                }
            }
        });

        return $count;
    }
}
