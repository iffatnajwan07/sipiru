<?php

return [
    // Versi kebijakan peminjaman. Naikkan versi → semua user diminta menyetujui ulang.
    'policy_version' => env('BOOKING_POLICY_VERSION', '1.0'),

    // true: pengajuan berstatus pending juga dianggap bentrok untuk pengajuan baru.
    // Booking APPROVED selalu dianggap bentrok (tidak dapat dinonaktifkan).
    'pending_blocks' => (bool) env('BOOKING_PENDING_BLOCKS', true),

    // Durasi minimum peminjaman (menit)
    'min_duration_minutes' => 30,

    // Jam operasional yang dapat dipilih
    'open_time' => '07:00',
    'close_time' => '22:00',

    // Pengingat
    'reminder_day_before_hours' => 24,
    'reminder_soon_minutes' => 60,

    'number_prefix' => 'PMJ',
];
