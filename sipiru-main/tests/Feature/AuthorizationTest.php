<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Building;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->activePeriod(2026);
    }

    public function test_user_cannot_access_another_users_booking(): void
    {
        $owner = $this->pegawai();
        $intruder = $this->eksternal();
        $booking = Booking::factory()->approved()->create(['user_id' => $owner->id]);

        $this->actingAs($intruder)->get(route('bookings.show', $booking))->assertForbidden();
        $this->actingAs($intruder)->get(route('bookings.pdf', $booking))->assertForbidden();
        $this->actingAs($intruder)->post(route('bookings.cancel', $booking))->assertForbidden();
        $this->actingAs($owner)->get(route('bookings.show', $booking))->assertOk();
    }

    public function test_user_cannot_access_admin_area(): void
    {
        $user = $this->pegawai();
        $building = $this->building();

        foreach (['/admin', '/admin/buildings', '/admin/rooms', '/admin/bookings', '/admin/users', '/admin/reports', '/admin/settings/email', '/admin/activity-logs'] as $url) {
            $this->actingAs($user)->get($url)->assertForbidden();
        }
        $this->actingAs($user)->post(route('admin.buildings.store'), ['name' => 'Hack', 'code' => 'HCK'])->assertForbidden();
        $this->actingAs($user)->put(route('admin.buildings.update', $building), ['name' => 'Hack', 'code' => 'HCK'])->assertForbidden();
        $this->actingAs($user)->put(route('admin.settings.email.update'), ['source' => 'env'])->assertForbidden();
        $this->assertSame(1, Building::count());
    }

    public function test_user_cannot_approve_own_booking(): void
    {
        $user = $this->pegawai();
        $booking = Booking::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->post(route('admin.bookings.approve', $booking))->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
        $this->get('/admin/buildings')->assertRedirect(route('admin.login'));
    }

    public function test_admin_is_redirected_away_from_user_area(): void
    {
        $this->actingAs($this->admin())->get('/dashboard')->assertRedirect(route('admin.dashboard'));
    }

    public function test_deactivated_user_is_logged_out(): void
    {
        $user = $this->pegawai(['is_active' => false]);

        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_ktp_is_private_and_only_visible_to_admin_or_owner(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('ktp/abc.pdf', '%PDF-1.4 test');
        $owner = $this->eksternal(['ktp_path' => 'ktp/abc.pdf']);
        $other = $this->eksternal();

        // Tidak ada URL publik ke storage privat
        $this->get('/storage/ktp/abc.pdf')->assertNotFound();

        $this->actingAs($owner)->get(route('profile.ktp'))->assertOk();
        $this->actingAs($this->admin())->get(route('admin.users.ktp', $owner))->assertOk();
        $this->actingAs($other)->get(route('admin.users.ktp', $owner))->assertForbidden();
    }
}
