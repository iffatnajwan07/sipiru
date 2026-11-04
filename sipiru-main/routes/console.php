<?php

use Illuminate\Support\Facades\Schedule;

/*
| Scheduler — dijalankan oleh service `scheduler` (php artisan schedule:work)
| atau cron production: * * * * * cd /path && php artisan schedule:run
*/
Schedule::command('bookings:close-finished')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('bookings:send-reminders')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('bookings:notify-pending')->dailyAt('07:00')->withoutOverlapping();
Schedule::command('queue:prune-failed --hours=720')->daily();
