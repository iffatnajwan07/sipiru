<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\BookingVerificationController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PolicyController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoomQrController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $user = auth()->user();

    if ($user) {
        return redirect()->route($user->isAdmin() ? 'admin.dashboard' : 'dashboard');
    }

    return redirect()->route('calendar');
})->name('home');

/*
|--------------------------------------------------------------------------
| Publik
|--------------------------------------------------------------------------
*/
// Kalender jadwal ruangan terbuka untuk publik tanpa login
Route::get('/kalender', CalendarController::class)->name('calendar');

Route::get('/booking/verify/{token}', BookingVerificationController::class)
    ->where('token', '[A-Za-z0-9]{20,64}')
    ->middleware('throttle:verify')
    ->name('bookings.verify');

// QR permanen ruangan → form pengajuan dengan ruangan terpilih (login bila perlu)
Route::get('/rooms/{token}/booking', RoomQrController::class)
    ->where('token', '[A-Za-z0-9]{20,64}')
    ->middleware(['auth', 'active'])
    ->name('rooms.qr-booking');

/*
|--------------------------------------------------------------------------
| Autentikasi user (Pegawai & Eksternal)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login/pegawai', [LoginController::class, 'internal'])->middleware('throttle:login')->name('login.internal');
    Route::post('/login/eksternal', [LoginController::class, 'external'])->middleware('throttle:login')->name('login.external');
    Route::get('/register', [RegisterController::class, 'show'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->middleware('throttle:register')->name('register.store');

    Route::get('/admin/login', [Admin\Auth\LoginController::class, 'show'])->name('admin.login');
    Route::post('/admin/login', [Admin\Auth\LoginController::class, 'store'])->middleware('throttle:login')->name('admin.login.store');
});

Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

/*
|--------------------------------------------------------------------------
| Area User
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'active', 'role:pegawai,eksternal'])->group(function () {
    Route::get('/kebijakan', [PolicyController::class, 'show'])->name('policy.show');
    Route::post('/kebijakan', [PolicyController::class, 'accept'])->name('policy.accept');

    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'password'])->name('profile.password');
    Route::get('/profile/ktp', [ProfileController::class, 'ktp'])->name('profile.ktp');

    Route::middleware('policy.accepted')->group(function () {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');

        Route::get('/peminjaman', [BookingController::class, 'index'])->name('bookings.index');
        Route::get('/peminjaman/ajukan', [BookingController::class, 'create'])->name('bookings.create');
        Route::post('/peminjaman', [BookingController::class, 'store'])->name('bookings.store');
        Route::get('/peminjaman/{booking}', [BookingController::class, 'show'])->name('bookings.show');
        Route::post('/peminjaman/{booking}/cancel', [BookingController::class, 'cancel'])->name('bookings.cancel');
        Route::get('/peminjaman/{booking}/bukti.pdf', [BookingController::class, 'pdf'])->name('bookings.pdf');

        Route::middleware('throttle:ajax')->group(function () {
            Route::post('/ajax/availability', [BookingController::class, 'availability'])->name('bookings.availability');
            Route::get('/ajax/buildings/{building}/rooms', [BookingController::class, 'rooms'])->name('bookings.rooms');
        });
    });
});

/*
|--------------------------------------------------------------------------
| Area Admin
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->middleware(['auth', 'active', 'role:admin'])->group(function () {
    Route::get('/', Admin\DashboardController::class)->name('dashboard');

    Route::middleware('throttle:ajax')->group(function () {
        Route::get('buildings/check-duplicate', [Admin\BuildingController::class, 'checkDuplicate'])->name('buildings.check');
        Route::get('rooms/check-duplicate', [Admin\RoomController::class, 'checkDuplicate'])->name('rooms.check');
    });

    Route::resource('buildings', Admin\BuildingController::class);
    Route::patch('buildings/{building}/toggle', [Admin\BuildingController::class, 'toggle'])->name('buildings.toggle');

    Route::resource('rooms', Admin\RoomController::class);
    Route::patch('rooms/{room}/toggle', [Admin\RoomController::class, 'toggle'])->name('rooms.toggle');
    Route::get('rooms/{room}/qr', [Admin\RoomController::class, 'qr'])->name('rooms.qr');
    Route::get('rooms/{room}/qr.png', [Admin\RoomController::class, 'qrPng'])->name('rooms.qr-png');

    Route::get('periods', [Admin\PeriodController::class, 'index'])->name('periods.index');
    Route::post('periods', [Admin\PeriodController::class, 'store'])->name('periods.store');
    Route::patch('periods/{period}/activate', [Admin\PeriodController::class, 'activate'])->name('periods.activate');
    Route::patch('periods/{period}/deactivate', [Admin\PeriodController::class, 'deactivate'])->name('periods.deactivate');

    Route::get('bookings', [Admin\BookingController::class, 'index'])->name('bookings.index');
    Route::get('bookings/{booking}', [Admin\BookingController::class, 'show'])->name('bookings.show');
    Route::post('bookings/{booking}/approve', [Admin\BookingController::class, 'approve'])->name('bookings.approve');
    Route::post('bookings/{booking}/reject', [Admin\BookingController::class, 'reject'])->name('bookings.reject');
    Route::post('bookings/{booking}/cancel', [Admin\BookingController::class, 'cancel'])->name('bookings.cancel');
    Route::get('bookings/{booking}/bukti.pdf', [Admin\BookingController::class, 'pdf'])->name('bookings.pdf');

    Route::get('calendar', CalendarController::class)->name('calendar');

    Route::get('users', [Admin\UserController::class, 'index'])->name('users.index');
    Route::get('users/{user}', [Admin\UserController::class, 'show'])->withTrashed()->name('users.show');
    Route::patch('users/{user}/toggle', [Admin\UserController::class, 'toggle'])->name('users.toggle');
    Route::get('users/{user}/ktp', [Admin\UserController::class, 'ktp'])->withTrashed()->name('users.ktp');

    Route::get('reports', [Admin\ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/export.csv', [Admin\ReportController::class, 'csv'])->name('reports.csv');
    Route::get('reports/export.pdf', [Admin\ReportController::class, 'pdf'])->name('reports.pdf');

    Route::get('settings/email', [Admin\EmailSettingController::class, 'edit'])->name('settings.email');
    Route::put('settings/email', [Admin\EmailSettingController::class, 'update'])->name('settings.email.update');
    Route::post('settings/email/test', [Admin\EmailSettingController::class, 'test'])->middleware('throttle:smtp-test')->name('settings.email.test');

    Route::get('activity-logs', [Admin\ActivityLogController::class, 'index'])->name('activity-logs.index');
    Route::get('login-activity', [Admin\ActivityLogController::class, 'logins'])->name('login-logs.index');
});