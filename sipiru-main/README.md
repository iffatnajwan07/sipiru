# Sistem Peminjaman Ruang USK

Aplikasi web untuk mengelola peminjaman ruangan di Universitas Syiah Kuala (USK):
pengajuan → pengecekan ketersediaan → persetujuan admin → notifikasi email → bukti PDF + QR Code.

**Stack:** Laravel 13 · PHP 8.4 (FPM) · MySQL 8.4 · Blade + Alpine.js · Tailwind CSS 4 · Vite · Nginx · Docker Compose

---

## Daftar Isi

1. [Requirements](#requirements)
2. [Instalasi (First Installation)](#instalasi-first-installation)
3. [Docker Setup](#docker-setup)
4. [Environment](#environment)
5. [Database, Migration & Seeder](#database-migration--seeder)
6. [Storage](#storage)
7. [Frontend](#frontend)
8. [Authentication](#authentication)
9. [SMTP / Email](#smtp--email)
10. [Scheduler & Queue](#scheduler--queue)
11. [Testing](#testing)
12. [Development](#development)
13. [Production](#production)
14. [Backup](#backup)
15. [SIMPEG Integration](#simpeg-integration)
16. [Aturan Bisnis Penting](#aturan-bisnis-penting)
17. [Troubleshooting](#troubleshooting)

---

## Requirements

Di komputer host **hanya** dibutuhkan:

| Tool | Versi minimal | Cek |
|---|---|---|
| Docker Engine | 24+ | `docker --version` |
| Docker Compose (plugin v2) | 2.20+ | `docker compose version` |
| Git | 2.x | `git --version` |

PHP, Composer, Node.js, dan npm **tidak perlu** dipasang di host — semuanya tersedia di dalam container `app`.

## Instalasi (First Installation)

```bash
git clone <repository> peminjaman-ruang-usk
cd peminjaman-ruang-usk

cp .env.example .env
# (opsional) sesuaikan UID/GID dengan user host: `id -u` dan `id -g`
# (opsional) jika port 80 sudah dipakai: set APP_PORT=8080 dan APP_URL=http://localhost:8080

docker compose up -d --build

docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
docker compose exec app php artisan storage:link

docker compose exec app npm install
docker compose exec app npm run build
```

Buka **http://localhost** (atau `http://localhost:<APP_PORT>`).

> Service `queue` dan `scheduler` otomatis menunggu sampai `composer install` dan `.env` tersedia,
> jadi wajar bila log-nya berisi "Menunggu 'composer install'…" pada instalasi pertama.

### Akun development (dari seeder)

Password semua akun: **`Password123`** — hanya untuk development, jangan dipakai di production.

| Peran | Login di | Identitas |
|---|---|---|
| Admin | `/admin/login` | `admin@usk.ac.id` atau NIP `198001012005011001` |
| Pegawai USK | `/login` → tab *Pegawai USK* | NIP `199001012020121001` |
| Pegawai (belum setuju kebijakan) | `/login` → tab *Pegawai USK* | NIP `198505052010121002` |
| Eksternal | `/login` → tab *Eksternal* | `eksternal@example.com` |

Seeder juga membuat Gedung ICT USK, Gedung FKIP, Gedung Rektorat, 8 ruangan (termasuk "Ruang Seminar A"
di ICT **dan** FKIP untuk mendemokan aturan duplikasi), periode tahun lalu (nonaktif) & tahun berjalan (aktif),
serta beberapa contoh booking.

## Docker Setup

```
Browser ──► nginx (:APP_PORT) ──► app (PHP-FPM :9000) ──► mysql (:3306)
                                     ▲
                 queue (queue:work) ─┤   mailpit (SMTP dev, UI :8025)
       scheduler (schedule:work) ────┘
```

| Service | Fungsi |
|---|---|
| `app` | PHP 8.4-FPM + Composer + Node 22 (kode di-bind mount dari host) |
| `nginx` | Web server, meneruskan request PHP ke `app` |
| `mysql` | MySQL 8.4, data di volume `mysql-data`; init script membuat database `<DB_DATABASE>_test` untuk test |
| `queue` | Worker antrean (pengiriman email) |
| `scheduler` | Menjalankan Laravel Scheduler setiap menit |
| `mailpit` | Penangkap email development — http://localhost:8025 (hapus di production) |

Struktur:

```
Dockerfile
compose.yaml
docker/
├── php/     php.ini, www.conf (FPM pool), wait-for-vendor.sh
├── nginx/   default.conf
└── mysql/   my.cnf, init/01-create-test-database.sh
```

Container `app` berjalan sebagai user `www-data` dengan UID/GID yang sama dengan user host
(`UID`/`GID` di `.env`), sehingga file yang dibuat container tidak dimiliki `root`.

## Environment

Semua konfigurasi ada di `.env` (disalin dari `.env.example`). **`.env` tidak boleh di-commit.**

| Variabel | Keterangan |
|---|---|
| `APP_PORT` | Port Nginx di host (default 80) |
| `APP_URL` | URL publik aplikasi — dipakai di link email & QR Code, **harus benar** |
| `UID`, `GID` | UID/GID user host |
| `DB_*` | Koneksi MySQL (`DB_HOST=mysql` di Docker) |
| `DB_ROOT_PASSWORD` | Password root MySQL (hanya saat volume pertama kali dibuat) |
| `FORWARD_DB_PORT` | Port MySQL di host, hanya `127.0.0.1` (default 33060) |
| `AUTH_INTERNAL_PROVIDER` | `local` (development) atau `simpeg` (production) |
| `SIMPEG_*` | Placeholder konfigurasi SIMPEG (lihat [SIMPEG Integration](#simpeg-integration)) |
| `BOOKING_PENDING_BLOCKS` | `true` = pengajuan *pending* juga dihitung bentrok (first come, first served) |
| `BOOKING_POLICY_VERSION` | Versi kebijakan; menaikkan versi → semua user wajib menyetujui ulang |
| `MAIL_*` | SMTP (lihat [SMTP / Email](#smtp--email)) |
| `MAIL_ADMIN_NOTIFICATION_ADDRESS` | Email tujuan notifikasi admin (kosong = semua admin aktif) |

Setelah mengubah `.env`: `docker compose exec app php artisan config:clear` lalu `docker compose restart queue scheduler`.

## Database, Migration & Seeder

```bash
docker compose exec app php artisan migrate            # jalankan migration baru
docker compose exec app php artisan migrate --seed     # migration + data development
docker compose exec app php artisan migrate:fresh --seed   # reset total (DEVELOPMENT SAJA)
docker compose exec app php artisan app:create-admin   # buat akun admin (production)
```

Tabel utama: `users`, `buildings`, `rooms`, `periods`, `bookings`, `booking_counters`,
`activity_logs`, `login_logs`, `settings`.

Akses MySQL dari host (DBeaver/TablePlus): `127.0.0.1:33060`, user/password sesuai `.env`.

## Storage

- **KTP** pengguna eksternal disimpan di disk `local` → `storage/app/private/ktp/` (nama file acak).
  Direktori ini **tidak** dapat diakses lewat URL publik; file hanya disajikan melalui route yang
  dilindungi policy (admin atau pemilik akun), dan setiap akses admin dicatat di Activity Log.
- Fitur *serve* bawaan Laravel untuk disk `local` dimatikan (`config/filesystems.php`).
- `php artisan storage:link` hanya diperlukan untuk file publik (`storage/app/public`); saat ini aplikasi tidak menyimpan file publik.
- Upload KTP: hanya PDF/JPG/JPEG/PNG (ekstensi **dan** MIME diperiksa), maksimal 2 MB.

## Frontend

- Blade + Alpine.js + Tailwind CSS 4 (plugin `@tailwindcss/forms`), dibundel Vite.
- Font: Plus Jakarta Sans (di-host sendiri saat build via `laravel-vite-plugin/fonts`).
- Ikon: [Lucide](https://lucide.dev) (lisensi ISC) — SVG disalin ke `resources/icons/` dan dirender
  server-side lewat komponen `<x-icon name="..."/>`. Untuk menambah ikon, salin file SVG dari paket
  `lucide-static` ke folder tersebut.

```bash
docker compose exec app npm run build        # build production
docker compose exec app npm run dev -- --host   # dev server + HMR (butuh port 5173 dipublish)
```

## Authentication

| Jenis user | Route | Mekanisme |
|---|---|---|
| Admin | `/admin/login` | Email/NIP + password lokal (bcrypt) |
| Pegawai USK | `/login` (tab Pegawai) | NIP + password melalui `AuthenticationProvider` |
| Eksternal | `/register`, `/login` (tab Eksternal) | Email + password lokal; registrasi wajib NIK (16 digit, unik), No. HP valid, upload KTP, password kuat |

Abstraksi autentikasi pegawai (`app/Auth/`):

```
AuthenticationProvider (interface)
├── LocalDevelopmentAuthProvider   ← development/testing (akun pegawai dari seeder)
└── SimpegAuthProvider             ← production (menunggu spesifikasi ICT USK)
InternalAuthenticator              ← provisioning otomatis user pegawai dari identitas provider
```

Modul peminjaman hanya bergantung pada interface, sehingga pergantian provider cukup lewat
`AUTH_INTERNAL_PROVIDER`. Password SIMPEG **tidak pernah disimpan** (kolom `password` pegawai tetap `NULL`
pada mode SIMPEG).

Kebijakan peminjaman: user (pegawai/eksternal) wajib mencentang *"Saya telah membaca dan menyetujui
kebijakan"* sebelum memakai sistem; `policy_accepted_at` dan `policy_version` disimpan.

Keamanan: CSRF (bawaan Laravel), escaping Blade (XSS), query builder/Eloquent (SQL injection),
`#[Fillable]` eksplisit (mass assignment), Laravel Policies (Building, Room, Booking, User, ActivityLog,
EmailSetting, Period), rate limiting (login, register, AJAX, verifikasi QR, test SMTP), bcrypt,
token QR acak 48 karakter (bukan ID database), proteksi IDOR (user hanya melihat booking miliknya).

## SMTP / Email

Dua mode, dipilih di **Admin → Settings → Email**:

1. **File .env** (default, disarankan production) — memakai `MAIL_*`:
   ```env
   MAIL_MAILER=smtp
   MAIL_HOST=smtp.usk.ac.id
   MAIL_PORT=587
   MAIL_USERNAME=...
   MAIL_PASSWORD=...
   MAIL_ENCRYPTION=tls        # tls = STARTTLS (587), ssl = implicit TLS (465), kosong = tanpa enkripsi
   MAIL_FROM_ADDRESS=no-reply@usk.ac.id
   MAIL_FROM_NAME="Peminjaman Ruang USK"
   ```
2. **Diatur dari aplikasi** — host, port, username, password, encryption, from disimpan di tabel `settings`.
   Password dienkripsi dengan `APP_KEY`, tidak pernah ditampilkan kembali, tidak dicatat di log.
   (Jika `APP_KEY` diganti, isi ulang password SMTP.)

Tombol **Test SMTP** mengirim email uji secara langsung dan menampilkan hasil/galatnya.

Notifikasi yang dikirim (via queue):

| Kejadian | Penerima |
|---|---|
| User mengajukan peminjaman | Admin |
| Admin approve / reject / cancel | User |
| Pengingat H-1 (≤24 jam sebelum mulai) | User |
| Pengingat menjelang waktu (≤60 menit) | User |
| Ringkasan pengajuan pending (07:00 setiap hari) | Admin |

Development: semua email ditangkap **Mailpit** → http://localhost:8025.

## Scheduler & Queue

Jadwal (`routes/console.php`):

| Command | Jadwal | Fungsi |
|---|---|---|
| `bookings:close-finished` | tiap 5 menit | approved yang sudah berakhir → *completed*; pending yang waktunya lewat → *rejected (kedaluwarsa)* |
| `bookings:send-reminders` | tiap 5 menit | pengingat H-1 & menjelang waktu (dikirim sekali) |
| `bookings:notify-pending` | 07:00 harian | ringkasan pengajuan pending ke admin |
| `queue:prune-failed` | harian | bersihkan failed jobs > 30 hari |

**Docker:** sudah berjalan otomatis di service `scheduler` (`php artisan schedule:work`) dan `queue`
(`php artisan queue:work`). Cek dengan `docker compose logs -f scheduler queue`.

**Tanpa Docker (server biasa):** tambahkan cron

```bash
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

dan jalankan worker queue dengan Supervisor/systemd: `php artisan queue:work --tries=3`.

Jalankan manual: `docker compose exec app php artisan schedule:list` /
`docker compose exec app php artisan bookings:send-reminders`.

## Testing

```bash
docker compose exec app php artisan test
docker compose exec app php artisan test --filter=BuildingManagementTest
```

Test berjalan di **MySQL** (database `peminjaman_usk_test`, dibuat otomatis oleh init script
MySQL) agar unique constraint, generated column, dan locking benar-benar teruji — bukan SQLite.
Jika `DB_DATABASE` diganti, sesuaikan nama database di `phpunit.xml`.

Cakupan (107 test): autentikasi (admin, pegawai via provider lokal, SIMPEG belum dikonfigurasi,
registrasi & login eksternal, rate limit, kebijakan), duplikasi gedung (case/spasi/unicode, similarity,
constraint DB), duplikasi ruang per gedung, periode aktif tunggal, booking (tersedia, overlap, bersebelahan,
masa lalu, ruang/gedung nonaktif, periode), approval/reject/cancel, otorisasi & IDOR, KTP privat, QR (valid,
invalid, dibatalkan, kedaluwarsa, QR ruangan), SMTP (enkripsi, mode env/database, test email), scheduler,
PDF, CSV, dan smoke test seluruh halaman.

Jika volume MySQL dibuat sebelum init script ada, buat database test manual:

```bash
docker compose exec mysql sh -c 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" -e "CREATE DATABASE IF NOT EXISTS peminjaman_usk_test; GRANT ALL ON peminjaman_usk_test.* TO \"$MYSQL_USER\"@\"%\";"'
```

## Development

```bash
docker compose up -d            # start
docker compose down             # stop (data MySQL tetap di volume)
docker compose restart          # restart semua service
docker compose logs -f          # log semua service
docker compose ps               # status

docker compose exec app php artisan                 # daftar perintah artisan
docker compose exec app php artisan migrate
docker compose exec app php artisan migrate:fresh --seed
docker compose exec app php artisan test
docker compose exec app ./vendor/bin/pint           # format kode
docker compose exec app php artisan tinker
```

Setelah mengubah kode yang dipakai worker (notifikasi, service): `docker compose restart queue`.

## Production

1. `.env` production:
   ```env
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://peminjaman.usk.ac.id
   LOG_LEVEL=warning
   SESSION_SECURE_COOKIE=true
   AUTH_INTERNAL_PROVIDER=simpeg     # setelah SimpegAuthProvider diimplementasikan
   ```
   Gunakan password database & SMTP yang kuat. Hapus service `mailpit` dari `compose.yaml`.
2. Set `opcache.validate_timestamps = 0` di `docker/php/php.ini`, lalu `docker compose up -d --build`.
3. Deploy:
   ```bash
   docker compose exec app composer install --no-dev --optimize-autoloader
   docker compose exec app npm ci && docker compose exec app npm run build
   docker compose exec app php artisan migrate --force
   docker compose exec app php artisan config:cache
   docker compose exec app php artisan route:cache
   docker compose exec app php artisan view:cache
   docker compose restart queue scheduler
   docker compose exec app php artisan app:create-admin
   ```
4. **Jangan pernah** menjalankan `migrate:fresh` / `db:seed` di production.
5. Pasang HTTPS (reverse proxy/TLS termination di depan Nginx) dan batasi akses port MySQL.

## Backup

Yang wajib dibackup:

| Data | Cara |
|---|---|
| Database MySQL | `docker compose exec mysql sh -c 'mysqldump -uroot -p"$MYSQL_ROOT_PASSWORD" --single-transaction peminjaman_usk' > backup-$(date +%F).sql` |
| File KTP & upload | `tar czf storage-$(date +%F).tgz storage/app/private` |
| Secrets | `.env` (terutama `APP_KEY` — tanpa ini password SMTP terenkripsi & sesi tidak dapat dibaca) — simpan di vault, **bukan** di Git |

Restore database: `docker compose exec -T mysql sh -c 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" peminjaman_usk' < backup.sql`

Jangan pernah commit: `.env`, password SMTP, kredensial SIMPEG, password database, secret production.

## SIMPEG Integration

**Saat ini:** `LocalDevelopmentAuthProvider` — memverifikasi NIP + password terhadap akun pegawai lokal hasil seeder.

**Production:** `SimpegAuthProvider` (`app/Auth/Providers/SimpegAuthProvider.php`) sudah terdaftar dan dapat
dipilih dengan `AUTH_INTERNAL_PROVIDER=simpeg`, namun **sengaja belum diimplementasikan** karena detail
integrasi belum tersedia. Saat dipilih, login pegawai menampilkan pesan bahwa integrasi belum dikonfigurasi.

Yang perlu diberikan oleh ICT USK:

1. Endpoint SIMPEG (base URL, environment staging/production)
2. Metode autentikasi (REST API / LDAP / SSO-CAS / OAuth2 / OIDC)
3. Client credentials (jika diperlukan)
4. Pemetaan field (NIP, nama, email, jabatan, no. HP, status aktif)
5. Mekanisme logout / session (terutama bila SSO)

Implementasi cukup di `SimpegAuthProvider::authenticate()` (kembalikan `InternalIdentity` atau `null`)
dan `logout()`. Modul peminjaman, policy, dan data booking tidak perlu diubah. Jika metode yang dipakai
berupa redirect SSO (bukan form NIP/password), tambahkan route callback yang memanggil `InternalAuthenticator`.

## Aturan Bisnis Penting

**Pencegahan duplikasi (dijamin di level database):**

| Entitas | Unique constraint |
|---|---|
| Gedung | `name_normalized`, `code_normalized` (hanya baris yang belum dihapus) |
| Ruang | `(building_id, name_normalized)`, `(building_id, code_normalized)` |
| Periode | `year`; dan hanya satu `status = active` (generated column `active_flag` UNIQUE) |
| Booking | tidak ada dua booking *approved* yang overlap pada ruangan yang sama (transaksi + row lock) |

Normalisasi: Unicode NFKC → hapus zero-width → semua whitespace (spasi, tab, NBSP) jadi satu spasi → trim → lowercase.
`"Gedung  ICT"`, `"GEDUNG ICT"`, `"gedung ict"` → `gedung ict`. Pengecekan kemiripan (Levenshtein + token)
hanya berupa peringatan di UI, bukan aturan keunikan.

**Overlap:** `new_start < existing_end AND new_end > existing_start` (09:00–11:00 vs 11:00–13:00 **tidak** bentrok).

**Concurrency:** pembuatan & approval booking dilakukan dalam transaksi dengan `SELECT … FOR UPDATE` pada
baris ruangan, sehingga pengajuan paralel untuk ruangan yang sama diproses bergantian.

**Data historis:** gedung yang masih memiliki ruangan dan ruangan yang memiliki riwayat peminjaman tidak dapat
dihapus — gunakan *Nonaktifkan*. Booking tidak dihapus, hanya dibatalkan.

## Troubleshooting

| Masalah | Solusi |
|---|---|
| `Bind for 0.0.0.0:80 failed: port is already allocated` | Port 80 dipakai aplikasi lain (Apache/XAMPP). Set `APP_PORT=8080` dan `APP_URL=http://localhost:8080` di `.env`, lalu `docker compose up -d` |
| `502 Bad Gateway` | Container `app` belum siap / `vendor` belum ada → `docker compose exec app composer install`; cek `docker compose logs app` |
| `Vite manifest not found` | `docker compose exec app npm install && docker compose exec app npm run build` |
| `No application encryption key` | `docker compose exec app php artisan key:generate` |
| `Permission denied` di `storage/` atau `bootstrap/cache` | Samakan `UID`/`GID` di `.env` dengan `id -u`/`id -g`, lalu `docker compose up -d --build` |
| `SQLSTATE[HY000] [2002] Connection refused` | MySQL masih inisialisasi → tunggu `docker compose ps` menunjukkan `healthy` |
| Test gagal: `Unknown database 'peminjaman_usk_test'` | Buat database test (lihat bagian [Testing](#testing)) |
| Email tidak terkirim | Cek `docker compose logs queue`; uji dari Admin → Settings → Email → Test SMTP; development: buka Mailpit |
| Link di email/QR salah host/port | Perbaiki `APP_URL`, lalu `php artisan config:clear` dan `docker compose restart queue` |
| Perubahan kode notifikasi tidak berpengaruh | `docker compose restart queue` (worker memuat kode sekali saat start) |
| Login "Too Many Attempts" | Rate limit login 5x/menit — tunggu 1 menit |
| Reset total development | `docker compose down -v` (menghapus data MySQL!) lalu ulangi langkah instalasi |
