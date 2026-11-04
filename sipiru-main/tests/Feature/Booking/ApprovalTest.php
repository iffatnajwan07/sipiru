<?php

namespace Tests\Feature\Booking;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use App\Notifications\BookingApproved;
use App\Notifications\BookingCancelled;
use App\Notifications\BookingRejected;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ApprovalTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Room $room;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->activePeriod(2026);
        $this->admin = $this->admin();
        $this->room = $this->room();
    }

    public function test_admin_can_approve_pending_booking(): void
    {
        $booking = Booking::factory()->create(['room_id' => $this->room->id]);

        $this->actingAs($this->admin)->post(route('admin.bookings.approve', $booking))->assertSessionHasNoErrors();

        $booking->refresh();
        $this->assertSame(BookingStatus::Approved, $booking->status);
        $this->assertSame($this->admin->id, $booking->approved_by);
        $this->assertNotNull($booking->approved_at);
        $this->assertSame(48, strlen($booking->verification_token));
        $this->assertDatabaseHas('activity_logs', ['action' => 'APPROVE_BOOKING', 'subject_id' => $booking->id]);
        Notification::assertSentTo($booking->user, BookingApproved::class);
    }

    public function test_cannot_approve_when_it_would_overlap_another_approved_booking(): void
    {
        config(['booking.pending_blocks' => false]);
        $a = Booking::factory()->at('2026-03-12 09:00', '2026-03-12 11:00')->create(['room_id' => $this->room->id]);
        $b = Booking::factory()->at('2026-03-12 10:00', '2026-03-12 12:00')->create(['room_id' => $this->room->id]);

        $this->actingAs($this->admin)->post(route('admin.bookings.approve', $a))->assertSessionHasNoErrors();
        $this->actingAs($this->admin)->post(route('admin.bookings.approve', $b))->assertSessionHasErrors('start_time');

        $this->assertSame(BookingStatus::Pending, $b->fresh()->status);
        $this->assertSame(1, Booking::status(BookingStatus::Approved)->count());
    }

    public function test_admin_can_reject_with_reason(): void
    {
        $booking = Booking::factory()->create(['room_id' => $this->room->id]);

        $this->actingAs($this->admin)->post(route('admin.bookings.reject', $booking), ['rejection_reason' => ''])->assertSessionHasErrors('rejection_reason');
        $this->actingAs($this->admin)->post(route('admin.bookings.reject', $booking), ['rejection_reason' => 'Ruangan dipakai rapat pimpinan'])->assertSessionHasNoErrors();

        $booking->refresh();
        $this->assertSame(BookingStatus::Rejected, $booking->status);
        $this->assertSame('Ruangan dipakai rapat pimpinan', $booking->rejection_reason);
        Notification::assertSentTo($booking->user, BookingRejected::class);
    }

    public function test_only_pending_bookings_can_be_approved(): void
    {
        $booking = Booking::factory()->approved()->create(['room_id' => $this->room->id]);

        $this->actingAs($this->admin)->post(route('admin.bookings.approve', $booking))->assertForbidden();
    }

    public function test_cannot_approve_booking_in_inactive_room(): void
    {
        $booking = Booking::factory()->create(['room_id' => $this->room->id]);
        $this->room->update(['is_active' => false]);

        $this->actingAs($this->admin)->post(route('admin.bookings.approve', $booking))->assertSessionHasErrors('booking');
        $this->assertSame(BookingStatus::Pending, $booking->fresh()->status);
    }

    public function test_admin_cancel_notifies_user(): void
    {
        $booking = Booking::factory()->approved()->create(['room_id' => $this->room->id]);

        $this->actingAs($this->admin)->post(route('admin.bookings.cancel', $booking), ['reason' => 'Kegiatan universitas'])->assertSessionHasNoErrors();

        $this->assertSame(BookingStatus::Cancelled, $booking->fresh()->status);
        Notification::assertSentTo($booking->user, BookingCancelled::class);
    }

    public function test_admin_can_filter_and_search_bookings(): void
    {
        $budi = $this->eksternal(['name' => 'Budi Santoso']);
        Booking::factory()->create(['room_id' => $this->room->id, 'user_id' => $budi->id, 'booking_number' => 'PMJ-20260310-0001']);
        Booking::factory()->approved()->create(['room_id' => $this->room->id, 'booking_number' => 'PMJ-20260310-0002']);

        $this->actingAs($this->admin)->get(route('admin.bookings.index', ['q' => 'Budi']))->assertSee('PMJ-20260310-0001')->assertDontSee('PMJ-20260310-0002');
        $this->actingAs($this->admin)->get(route('admin.bookings.index', ['status' => 'approved']))->assertSee('PMJ-20260310-0002')->assertDontSee('PMJ-20260310-0001');
        $this->actingAs($this->admin)->get(route('admin.bookings.index', ['q' => $budi->nik]))->assertSee('PMJ-20260310-0001');
    }

    public function test_approved_booking_proof_pdf_can_be_downloaded(): void
    {
        $booking = Booking::factory()->approved()->create(['room_id' => $this->room->id]);

        $this->actingAs($booking->user)->get(route('bookings.pdf', $booking))
            ->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->actingAs($this->admin)->get(route('admin.bookings.pdf', $booking))->assertOk();
    }

    public function test_pending_booking_has_no_proof(): void
    {
        $booking = Booking::factory()->create(['room_id' => $this->room->id]);

        $this->actingAs($booking->user)->get(route('bookings.pdf', $booking))->assertForbidden();
    }
}
