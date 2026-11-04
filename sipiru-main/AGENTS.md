# Sistem Peminjaman Ruang USK — panduan untuk agent

Laravel 13 + Blade/Alpine + Tailwind 4, dijalankan **hanya lewat Docker** (jangan install PHP/Composer/Node di host).

## Perintah
- Semua perintah PHP/Node: `docker compose exec app <cmd>` (mis. `php artisan test`, `./vendor/bin/pint`, `npm run build`).
- Test memakai MySQL database `peminjaman_usk_test` (bukan SQLite) — lihat `phpunit.xml`.
- Setelah mengubah notifikasi/service yang dipakai worker: `docker compose restart queue`.

## Arsitektur
- Logika bisnis ada di `app/Services` (BookingService, BuildingService, RoomService, PeriodService, MailSettings); controller tipis.
- Operasi penting memakai `DB::transaction` + `lockForUpdate()` pada baris ruangan (booking) — pertahankan.
- Keunikan gedung/ruang dijamin unique index pada kolom `*_normalized` (via generated column `*_unique_key`); normalisasi di `App\Support\NameNormalizer` dan dihitung di event `saving` model.
- Autentikasi pegawai lewat `App\Auth\Contracts\AuthenticationProvider` (local/simpeg). Jangan mengarang API SIMPEG.
- Otorisasi lewat Policies di `app/Policies`; setiap tindakan admin dicatat via `ActivityLogger`.
- Ikon: `<x-icon name="..."/>` membaca SVG Lucide di `resources/icons/`.
