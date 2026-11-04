<?php

namespace Database\Seeders;

use App\Enums\BookingStatus;
use App\Enums\PeriodStatus;
use App\Enums\UserType;
use App\Models\Booking;
use App\Models\Building;
use App\Models\Period;
use App\Models\Room;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Data DEVELOPMENT. Password di bawah hanya untuk lokal — JANGAN dipakai di production.
 */
class DatabaseSeeder extends Seeder
{
    public const DEV_PASSWORD = 'Password123';

    public function run(): void
    {
        $policy = ['policy_accepted_at' => now(), 'policy_version' => config('booking.policy_version')];

        $admin = $this->user([
            'name' => 'Administrator Sarpras', 'email' => 'admin@usk.ac.id', 'nip' => '198001012005011001',
            'user_type' => UserType::Admin, 'position' => 'Admin Sarana & Prasarana',
        ]);

        $pegawai = $this->user([
            'name' => 'Khairil Ilmi', 'email' => 'pegawai@usk.ac.id', 'nip' => '199001012020121001',
            'user_type' => UserType::Pegawai, 'position' => 'Staf ICT', 'phone' => '081234567890', ...$policy,
        ]);

        $this->user([
            'name' => 'Dosen Belum Setuju Kebijakan', 'email' => 'dosen@usk.ac.id', 'nip' => '198505052010121002',
            'user_type' => UserType::Pegawai, 'position' => 'Dosen', 'phone' => '081234567891',
        ]);

        Storage::disk('local')->put('ktp/contoh-ktp.pdf', "%PDF-1.4\n% Dummy KTP untuk development\n");
        $eksternal = $this->user([
            'name' => 'Budi Santoso', 'email' => 'eksternal@example.com', 'nik' => '1171010101900001',
            'user_type' => UserType::Eksternal, 'phone' => '082112345678', 'ktp_path' => 'ktp/contoh-ktp.pdf', ...$policy,
        ]);

        Period::updateOrCreate(['year' => now()->year - 1], ['status' => PeriodStatus::Inactive]);
        $period = Period::updateOrCreate(['year' => now()->year], ['status' => PeriodStatus::Active]);

        $ict = Building::firstOrCreate(['code' => 'ICT'], ['name' => 'Gedung ICT USK', 'address' => 'Jl. Teuku Nyak Arief, Darussalam, Banda Aceh', 'description' => 'Gedung UPT Teknologi Informasi dan Komunikasi']);
        $fkip = Building::firstOrCreate(['code' => 'FKIP'], ['name' => 'Gedung FKIP', 'address' => 'Kopelma Darussalam, Banda Aceh']);
        $rektorat = Building::firstOrCreate(['code' => 'REK'], ['name' => 'Gedung Rektorat', 'address' => 'Jl. Teuku Nyak Arief No. 441, Banda Aceh']);

        $rooms = [
            [$ict, 'ICT-SA-01', 'Ruang Seminar A', '1', 50, 'Proyektor, AC, sound system'],
            [$ict, 'ICT-SB-01', 'Ruang Seminar B', '1', 30, 'Proyektor, AC'],
            [$ict, 'ICT-LAB-01', 'Lab Komputer 1', '2', 40, '40 unit PC'],
            [$ict, 'ICT-RR-03', 'Ruang Rapat', '3', 20, 'Smart TV, video conference'],
            [$fkip, 'FKIP-SA-01', 'Ruang Seminar A', '1', 80, 'Proyektor, AC'],
            [$fkip, 'FKIP-AUD', 'Auditorium FKIP', '1', 300, 'Panggung, sound system'],
            [$rektorat, 'REK-AULA', 'Aula Rektorat', '2', 250, 'Panggung, sound system, AC'],
            [$rektorat, 'REK-RR-01', 'Ruang Rapat Senat', '3', 40, 'Meja rapat, mikrofon'],
        ];
        foreach ($rooms as [$building, $code, $name, $floor, $capacity, $desc]) {
            Room::firstOrCreate(['building_id' => $building->id, 'code' => $code], [
                'name' => $name, 'floor' => $floor, 'capacity' => $capacity, 'description' => $desc,
            ]);
        }

        if (Booking::exists()) {
            return;
        }

        $seminarA = Room::where('code', 'ICT-SA-01')->first();
        $lab = Room::where('code', 'ICT-LAB-01')->first();
        $aula = Room::where('code', 'REK-AULA')->first();
        $tomorrow = now()->addDay()->startOfDay();

        $this->booking($pegawai, $seminarA, $period, $tomorrow->copy()->setTime(9, 0), 2, BookingStatus::Approved, $admin, 'Seminar proposal mahasiswa');
        $this->booking($eksternal, $seminarA, $period, $tomorrow->copy()->setTime(13, 0), 2, BookingStatus::Pending, null, 'Workshop komunitas');
        $this->booking($pegawai, $lab, $period, $tomorrow->copy()->addDays(2)->setTime(8, 0), 3, BookingStatus::Pending, null, 'Pelatihan aplikasi');
        $this->booking($eksternal, $aula, $period, $tomorrow->copy()->addDays(5)->setTime(10, 0), 4, BookingStatus::Rejected, $admin, 'Acara seminar nasional', 'Aula digunakan untuk kegiatan universitas.');
        $this->booking($pegawai, $aula, $period, now()->subDays(3)->setTime(9, 0), 2, BookingStatus::Completed, $admin, 'Rapat koordinasi');
    }

    private function user(array $attributes): User
    {
        $user = User::withTrashed()->firstOrNew(['email' => $attributes['email']]);
        $user->forceFill($attributes + ['is_active' => true, 'email_verified_at' => now()]);
        $user->password = self::DEV_PASSWORD;
        $user->save();

        return $user;
    }

    private function booking(User $user, Room $room, Period $period, $start, int $hours, BookingStatus $status, ?User $admin, string $notes, ?string $reason = null): void
    {
        $date = now()->toDateString();
        DB::table('booking_counters')->insertOrIgnore(['date' => $date, 'last_number' => 0]);
        DB::table('booking_counters')->where('date', $date)->increment('last_number');
        $n = DB::table('booking_counters')->where('date', $date)->value('last_number');

        $booking = new Booking(['notes' => $notes]);
        $booking->forceFill([
            'booking_number' => sprintf('PMJ-%s-%04d', now()->format('Ymd'), $n),
            'user_id' => $user->id,
            'room_id' => $room->id,
            'period_id' => $period->id,
            'start_at' => $start,
            'end_at' => $start->copy()->addHours($hours),
            'status' => $status,
            'approved_by' => $admin?->id,
            'approved_at' => $admin ? now() : null,
            'rejection_reason' => $reason,
            'verification_token' => in_array($status, [BookingStatus::Approved, BookingStatus::Completed]) ? Str::random(48) : null,
            'completed_at' => $status === BookingStatus::Completed ? now() : null,
        ])->save();
    }
}
