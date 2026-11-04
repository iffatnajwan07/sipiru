<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Notifications\BookingReminder;
use App\Notifications\PendingBookingsDigest;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SchedulerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->activePeriod(2026);
    }

    public function test_scheduler_tasks_are_registered(): void
    {
        $commands = collect(app(Schedule::class)->events())->pluck('command')->implode(' ');

        foreach (['bookings:close-finished', 'bookings:send-reminders', 'bookings:notify-pending'] as $command) {
            $this->assertStringContainsString($command, $commands);
        }
    }

    public function test_reminders_are_sent_once(): void
    {
        // now = 2026-03-10 08:00
        $tomorrow = Booking::factory()->approved()->at('2026-03-11 07:00', '2026-03-11 09:00')->create();
        $soon = Booking::factory()->approved()->at('2026-03-10 08:30', '2026-03-10 10:00')->create();
        $farAway = Booking::factory()->approved()->at('2026-03-20 09:00', '2026-03-20 10:00')->create();
        $pending = Booking::factory()->at('2026-03-10 08:45', '2026-03-10 10:00')->create();

        $this->artisan('bookings:send-reminders')->assertSuccessful();
        $this->artisan('bookings:send-reminders')->assertSuccessful();

        Notification::assertSentToTimes($tomorrow->user, BookingReminder::class, 1);
        Notification::assertSentTo($tomorrow->user, BookingReminder::class, fn ($n) => $n->type === 'day');
        Notification::assertSentTo($soon->user, BookingReminder::class, fn ($n) => $n->type === 'soon');
        Notification::assertNotSentTo($farAway->user, BookingReminder::class);
        Notification::assertNotSentTo($pending->user, BookingReminder::class);
    }

    public function test_finished_bookings_are_completed_and_stale_pending_expired(): void
    {
        $done = Booking::factory()->approved()->at('2026-03-09 09:00', '2026-03-09 11:00')->create();
        $upcoming = Booking::factory()->approved()->at('2026-03-11 09:00', '2026-03-11 11:00')->create();
        $stale = Booking::factory()->at('2026-03-09 13:00', '2026-03-09 14:00')->create();

        $this->artisan('bookings:close-finished')->assertSuccessful();

        $this->assertSame(BookingStatus::Completed, $done->fresh()->status);
        $this->assertNotNull($done->fresh()->completed_at);
        $this->assertSame(BookingStatus::Approved, $upcoming->fresh()->status);
        $this->assertSame(BookingStatus::Rejected, $stale->fresh()->status);
    }

    public function test_pending_digest_is_sent_to_admin(): void
    {
        $this->admin(['email' => 'admin@usk.ac.id']);
        Booking::factory()->count(2)->sequence(
            ['start_at' => '2026-03-12 09:00', 'end_at' => '2026-03-12 10:00'],
            ['start_at' => '2026-03-13 09:00', 'end_at' => '2026-03-13 10:00'],
        )->create();

        $this->artisan('bookings:notify-pending')->assertSuccessful();

        Notification::assertSentTo(new AnonymousNotifiable, PendingBookingsDigest::class, fn ($n) => $n->total === 2);
    }
}
