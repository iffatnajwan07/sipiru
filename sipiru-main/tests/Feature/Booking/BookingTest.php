<?php

namespace Tests\Feature\Booking;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Period;
use App\Models\Room;
use App\Models\User;
use App\Notifications\BookingSubmitted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class BookingTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Room $room;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->activePeriod(2026);
        $this->user = $this->pegawai();
        $this->room = $this->room();
    }

    private function approvedBooking(string $start, string $end, ?Room $room = null): Booking
    {
        return Booking::factory()->approved()->at($start, $end)->create(['room_id' => ($room ?? $this->room)->id]);
    }

    public function test_user_can_book_available_room(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($this->user)->post(route('bookings.store'), $this->bookingPayload($this->room));

        $booking = Booking::firstOrFail();
        $response->assertRedirect(route('bookings.show', $booking));
        $this->assertSame(BookingStatus::Pending, $booking->status);
        $this->assertSame('PMJ-20260310-0001', $booking->booking_number);
        $this->assertSame('2026-03-12 09:00:00', $booking->start_at->toDateTimeString());
        $this->assertSame($this->user->id, $booking->user_id);
        $this->assertDatabaseHas('activity_logs', ['action' => 'CREATE_BOOKING', 'subject_id' => $booking->id]);

        Notification::assertSentTo(new AnonymousNotifiable, BookingSubmitted::class,
            fn ($n, $channels, $notifiable) => in_array($admin->email, (array) $notifiable->routes['mail'], true));
    }

    public function test_booking_numbers_are_sequential_per_day(): void
    {
        $this->actingAs($this->user)->post(route('bookings.store'), $this->bookingPayload($this->room, '2026-03-12', '08:00', '09:00'));
        $this->actingAs($this->user)->post(route('bookings.store'), $this->bookingPayload($this->room, '2026-03-12', '10:00', '11:00'));

        $this->assertSame(['PMJ-20260310-0001', 'PMJ-20260310-0002'], Booking::orderBy('id')->pluck('booking_number')->all());
    }

    /** 09:00-11:00 (approved) vs 10:00-12:00 → BENTROK */
    public function test_overlapping_booking_is_rejected(): void
    {
        $this->approvedBooking('2026-03-12 09:00', '2026-03-12 11:00');

        $this->actingAs($this->user)->post(route('bookings.store'), $this->bookingPayload($this->room, '2026-03-12', '10:00', '12:00'))
            ->assertSessionHasErrors(['start_time' => 'Ruangan tidak tersedia pada waktu tersebut. Digunakan: 09:00 - 11:00.']);
        $this->assertSame(1, Booking::count());
    }

    public function test_booking_fully_containing_existing_is_rejected(): void
    {
        $this->approvedBooking('2026-03-12 09:00', '2026-03-12 10:00');

        $this->actingAs($this->user)->post(route('bookings.store'), $this->bookingPayload($this->room, '2026-03-12', '08:00', '12:00'))
            ->assertSessionHasErrors('start_time');
    }

    /** 09:00-11:00 vs 11:00-13:00 → TIDAK BENTROK */
    public function test_adjacent_booking_is_allowed(): void
    {
        $this->approvedBooking('2026-03-12 09:00', '2026-03-12 11:00');

        $this->actingAs($this->user)->post(route('bookings.store'), $this->bookingPayload($this->room, '2026-03-12', '11:00', '13:00'))
            ->assertSessionHasNoErrors();
        $this->actingAs($this->user)->post(route('bookings.store'), $this->bookingPayload($this->room, '2026-03-12', '07:00', '09:00'))
            ->assertSessionHasNoErrors();
        $this->assertSame(3, Booking::count());
    }

    public function test_same_time_in_different_room_is_allowed(): void
    {
        $this->approvedBooking('2026-03-12 09:00', '2026-03-12 11:00');

        $this->actingAs($this->user)->post(route('bookings.store'), $this->bookingPayload($this->room(), '2026-03-12', '09:00', '11:00'))
            ->assertSessionHasNoErrors();
    }

    public function test_pending_booking_blocks_when_policy_enabled(): void
    {
        Booking::factory()->at('2026-03-12 09:00', '2026-03-12 11:00')->create(['room_id' => $this->room->id]);

        $this->actingAs($this->user)->post(route('bookings.store'), $this->bookingPayload($this->room, '2026-03-12', '10:00', '12:00'))
            ->assertSessionHasErrors('start_time');

        config(['booking.pending_blocks' => false]);
        $this->actingAs($this->user)->post(route('bookings.store'), $this->bookingPayload($this->room, '2026-03-12', '10:00', '12:00'))
            ->assertSessionHasNoErrors();
    }

    public function test_rejected_and_cancelled_bookings_do_not_block(): void
    {
        Booking::factory()->at('2026-03-12 09:00', '2026-03-12 11:00')->create(['room_id' => $this->room->id, 'status' => BookingStatus::Rejected]);
        Booking::factory()->at('2026-03-12 09:00', '2026-03-12 11:00')->create(['room_id' => $this->room->id, 'status' => BookingStatus::Cancelled]);

        $this->actingAs($this->user)->post(route('bookings.store'), $this->bookingPayload($this->room))->assertSessionHasNoErrors();
    }

    public function test_past_booking_is_rejected(): void
    {
        $this->actingAs($this->user)->post(route('bookings.store'), $this->bookingPayload($this->room, '2026-03-09'))
            ->assertSessionHasErrors(['start_time' => 'Tidak dapat meminjam untuk waktu yang sudah lewat.']);
        // Hari ini, jam yang sudah lewat (sekarang 08:00)
        $this->actingAs($this->user)->post(route('bookings.store'), $this->bookingPayload($this->room, '2026-03-10', '07:00', '09:00'))
            ->assertSessionHasErrors('start_time');
    }

    public function test_start_must_be_before_end(): void
    {
        $this->actingAs($this->user)->post(route('bookings.store'), $this->bookingPayload($this->room, '2026-03-12', '11:00', '09:00'))
            ->assertSessionHasErrors('end_time');
        $this->actingAs($this->user)->post(route('bookings.store'), $this->bookingPayload($this->room, '2026-03-12', '09:00', '09:00'))
            ->assertSessionHasErrors('end_time');
    }

    public function test_inactive_room_is_rejected(): void
    {
        $this->room->update(['is_active' => false]);

        $this->actingAs($this->user)->post(route('bookings.store'), $this->bookingPayload($this->room))
            ->assertSessionHasErrors(['room_id' => 'Ruangan sedang tidak aktif dan tidak dapat dipinjam.']);
    }

    public function test_inactive_building_is_rejected(): void
    {
        $this->room->building->update(['is_active' => false]);

        $this->actingAs($this->user)->post(route('bookings.store'), $this->bookingPayload($this->room))
            ->assertSessionHasErrors('room_id');
        $this->assertSame(0, Booking::count());
    }

    public function test_booking_requires_active_period_and_matching_year(): void
    {
        $this->actingAs($this->user)->post(route('bookings.store'), $this->bookingPayload($this->room, '2027-01-05'))
            ->assertSessionHasErrors(['date' => 'Tanggal peminjaman harus berada dalam periode aktif (2026).']);

        Period::query()->update(['status' => 'inactive']);
        $this->actingAs($this->user)->post(route('bookings.store'), $this->bookingPayload($this->room))
            ->assertSessionHasErrors('date');
    }

    public function test_availability_endpoint(): void
    {
        $this->approvedBooking('2026-03-12 09:00', '2026-03-12 11:00');

        $this->actingAs($this->user)->postJson(route('bookings.availability'), $this->bookingPayload($this->room, '2026-03-12', '10:00', '12:00'))
            ->assertOk()->assertJson(['available' => false, 'conflicts' => [['time' => '09:00 - 11:00', 'status' => 'Disetujui']]]);

        $this->actingAs($this->user)->postJson(route('bookings.availability'), $this->bookingPayload($this->room, '2026-03-12', '11:00', '12:00'))
            ->assertOk()->assertJson(['available' => true, 'errors' => [], 'conflicts' => []]);
    }

    public function test_availability_does_not_leak_other_users_identity(): void
    {
        $other = $this->eksternal(['name' => 'Nama Rahasia']);
        Booking::factory()->approved()->at('2026-03-12 09:00', '2026-03-12 11:00')->create(['room_id' => $this->room->id, 'user_id' => $other->id]);

        $this->actingAs($this->user)->postJson(route('bookings.availability'), $this->bookingPayload($this->room))
            ->assertDontSee('Nama Rahasia');
    }

    public function test_user_can_cancel_own_booking(): void
    {
        $booking = Booking::factory()->create(['user_id' => $this->user->id, 'room_id' => $this->room->id]);

        $this->actingAs($this->user)->post(route('bookings.cancel', $booking))->assertSessionHasNoErrors();
        $this->assertSame(BookingStatus::Cancelled, $booking->fresh()->status);
        $this->assertNotNull($booking->fresh()->cancelled_at);
    }

    public function test_user_history_only_shows_own_bookings(): void
    {
        Booking::factory()->create(['user_id' => $this->user->id, 'room_id' => $this->room->id, 'booking_number' => 'PMJ-MINE-0001']);
        Booking::factory()->create(['room_id' => $this->room->id, 'booking_number' => 'PMJ-OTHER-0001']);

        $this->actingAs($this->user)->get(route('bookings.index'))->assertOk()->assertSee('PMJ-MINE-0001')->assertDontSee('PMJ-OTHER-0001');
        $this->actingAs($this->user)->get(route('dashboard'))->assertOk()->assertDontSee('PMJ-OTHER-0001');
    }

    public function test_create_form_preselects_room(): void
    {
        $this->actingAs($this->user)->get(route('bookings.create', ['room' => $this->room->id]))
            ->assertOk()->assertSee('otomatis terpilih', false);
    }
}
