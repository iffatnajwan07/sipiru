<?php

namespace App\Console\Commands;

use App\Services\BookingService;
use Illuminate\Console\Attributes\AsCommand;
use Illuminate\Console\Command;

#[AsCommand(name: 'bookings:close-finished', description: 'Tandai booking yang sudah berakhir sebagai selesai & pengajuan kedaluwarsa')]
class CloseFinishedBookings extends Command
{
    protected $signature = 'bookings:close-finished';

    public function handle(BookingService $bookings): int
    {
        $result = $bookings->closeFinished();
        $this->info("Selesai: {$result['completed']}, pending kedaluwarsa: {$result['expired']}");

        return self::SUCCESS;
    }
}
