<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QrCodeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->activePeriod(2026);
    }

    public function test_valid_qr_shows_booking_without_sensitive_data(): void
    {
        $user = $this->eksternal(['name' => 'Budi Rahasia', 'nik' => '1171010101900001']);
        $booking = Booking::factory()->approved()->create(['user_id' => $user->id]);

        $this->get(route('bookings.verify', $booking->verification_token))
            ->assertOk()
            ->assertSee('Booking Valid')
            ->assertSee($booking->booking_number)
            ->assertSee($booking->room->name)
            ->assertDontSee('Budi Rahasia')
            ->assertDontSee('1171010101900001');
    }

    public function test_invalid_qr_returns_not_found(): void
    {
        $this->get('/booking/verify/'.str_repeat('a', 48))->assertNotFound()->assertSee('QR Tidak Valid');
    }

    public function test_database_id_is_not_accepted_as_token(): void
    {
        $booking = Booking::factory()->approved()->create();

        $this->get('/booking/verify/'.$booking->id)->assertNotFound();
    }

    public function test_cancelled_booking_qr_is_not_valid(): void
    {
        $booking = Booking::factory()->approved()->create();
        $booking->forceFill(['status' => BookingStatus::Cancelled])->save();

        $this->get(route('bookings.verify', $booking->verification_token))->assertOk()->assertSee('Booking Tidak Berlaku')->assertDontSee('Booking Valid');
    }

    public function test_expired_booking_qr(): void
    {
        $booking = Booking::factory()->approved()->at('2026-03-01 09:00', '2026-03-01 11:00')->create();
        $booking->forceFill(['status' => BookingStatus::Completed])->save();

        $this->get(route('bookings.verify', $booking->verification_token))->assertOk()->assertSee('Peminjaman Telah Berakhir');
    }

    public function test_room_qr_redirects_to_booking_form_with_room_selected(): void
    {
        $room = $this->room();

        $this->get(route('rooms.qr-booking', $room->public_token))->assertRedirect(route('login'));

        $this->actingAs($this->pegawai())->get(route('rooms.qr-booking', $room->public_token))
            ->assertRedirect(route('bookings.create', ['room' => $room->id]));
    }

    public function test_inactive_room_qr_shows_message(): void
    {
        $room = $this->room(['is_active' => false]);

        $this->actingAs($this->pegawai())->get(route('rooms.qr-booking', $room->public_token))
            ->assertRedirect(route('bookings.create'))->assertSessionHas('error');
    }
}
