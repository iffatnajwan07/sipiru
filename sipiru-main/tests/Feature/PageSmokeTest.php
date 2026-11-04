<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Semua halaman utama dapat dirender tanpa error untuk tiap peran. */
class PageSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->seed(DatabaseSeeder::class);
    }

    public function test_admin_pages_render(): void
    {
        $admin = User::where('email', 'admin@usk.ac.id')->first();
        $booking = Booking::first();
        $room = $booking->room;
        $user = $booking->user;

        $urls = [
            route('admin.dashboard'), route('admin.buildings.index'), route('admin.buildings.create'),
            route('admin.buildings.show', $room->building), route('admin.buildings.edit', $room->building),
            route('admin.rooms.index'), route('admin.rooms.create'), route('admin.rooms.show', $room), route('admin.rooms.edit', $room),
            route('admin.periods.index'), route('admin.bookings.index'), route('admin.bookings.index', ['status' => 'pending']),
            route('admin.bookings.show', $booking), route('admin.calendar'), route('admin.calendar', ['month' => '2026-04']),
            route('admin.users.index'), route('admin.users.show', $user), route('admin.reports.index'),
            route('admin.reports.index', ['status' => 'approved', 'from' => '2026-01-01', 'to' => '2026-12-31']),
            route('admin.settings.email'), route('admin.activity-logs.index'), route('admin.login-logs.index'),
        ];
        foreach ($urls as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }

        $this->actingAs($admin)->get(route('admin.reports.csv'))->assertOk()->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->actingAs($admin)->get(route('admin.reports.pdf'))->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_user_pages_render(): void
    {
        $pegawai = User::where('email', 'pegawai@usk.ac.id')->first();
        $booking = $pegawai->bookings()->first();

        foreach ([route('dashboard'), route('bookings.index'), route('bookings.create'), route('bookings.show', $booking),
            route('calendar'), route('policy.show'), route('profile.show')] as $url) {
            $this->actingAs($pegawai)->get($url)->assertOk();
        }
    }

    public function test_guest_pages_render(): void
    {
        foreach (['/login', '/register', '/admin/login'] as $url) {
            $this->get($url)->assertOk();
        }
    }
}
