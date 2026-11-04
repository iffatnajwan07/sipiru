<?php

use App\Auth\Providers\LocalDevelopmentAuthProvider;
use App\Auth\Providers\SimpegAuthProvider;

return [
    /*
    | Provider autentikasi untuk pegawai internal USK.
    |  - local  : LocalDevelopmentAuthProvider (development / testing)
    |  - simpeg : SimpegAuthProvider (production — detail integrasi dari ICT USK)
    */
    'internal' => env('AUTH_INTERNAL_PROVIDER', 'local'),

    'providers' => [
        'local' => LocalDevelopmentAuthProvider::class,
        'simpeg' => SimpegAuthProvider::class,
    ],

    'simpeg' => [
        'base_url' => env('SIMPEG_BASE_URL'),
        'client_id' => env('SIMPEG_CLIENT_ID'),
        'client_secret' => env('SIMPEG_CLIENT_SECRET'),
        'timeout' => (int) env('SIMPEG_TIMEOUT', 10),
    ],
];
