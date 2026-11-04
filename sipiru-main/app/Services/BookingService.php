<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Period;
use App\Models\Room;
use App\Models\User;
use App\Notifications\BookingApproved;
use App\Notifications\BookingCancelled;
use App\Notifications\BookingRejected;
use App\Notifications\BookingSubmitted;
use Illuminate\Notifications\Notification as BaseNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class BookingService
{
    public function __construct(
        private ActivityLogger $logger,
        private MailSettings $mailSettings,
    ) {}

    /**
     * Validasi aturan peminjaman (selain bentrok jadwal).
     *
     * @return array<string, string> daftar error (kosong = valid)
     */
    public function ruleErrors(Room $room, Carbon $start, Carbon $end): array
    {
        $errors = [];
        $room->loadMissing('building');

        if (! $room->is_active || $room->trashed()) {
            $errors['room_id'] = 'Ruangan sedang tidak aktif dan tidak dapat dipinjam.';
        } elseif (! $room->building || ! $room->building->is_active || $room->building->trashed()) {
            $errors['room_id'] = 'Gedung ruangan ini sedang tidak aktif sehingga ruangan tidak dapat dipinjam.';
        }

        $period = Period::current();
        if (! $period) {
            $errors['date'] = 'Belum ada periode peminjaman yang aktif. Hubungi admin.';
        } elseif ($start->year !== $period->year) {
            $errors['date'] = "Tanggal peminjaman harus berada dalam periode aktif ({$period->year}).";
        }

        if ($start->gte($end)) {
            $errors['end_time'] = 'Jam selesai harus setelah jam mulai.';
        } elseif ($start->diffInMinutes($end) < config('booking.min_duration_minutes')) {
            $errors['end_time'] = 'Durasi peminjaman minimal '.config('booking.min_duration_minutes').' menit.';
        }

        if ($start->lte(now())) {
            $errors['start_time'] = 'Tidak dapat meminjam untuk waktu yang sudah lewat.';
        }

        return $errors;
    }

    /**
     * Booking yang bentrok: new_start < existing_end AND new_end > existing_start.
     */
    public function conflicts(Room $room, Carbon $start, Carbon $end, ?bool $includePending = null, ?int $ignoreId = null): Collection
    {
        $includePending ??= config('booking.pending_blocks');
        $statuses = [BookingStatus::Approved, BookingStatus::Completed];
        if ($includePending) {
            $statuses[] = BookingStatus::Pending;
        }

        return Booking::query()
            ->where('room_id', $room->getKey())
            ->status(...$statuses)
            ->overlapping($start, $end)
            ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
            ->orderBy('start_at')
            ->get();
    }

    /** Hasil cek ketersediaan untuk UI. Tidak membocorkan identitas peminjam lain. */
    public function availability(Room $room, Carbon $start, Carbon $end): array
    {
        $errors = $this->ruleErrors($room, $start, $end);
        $conflicts = $errors === [] ? $this->conflicts($room, $start, $end) : collect();

        return [
            'available' => $errors === [] && $conflicts->isEmpty(),
            'errors' => array_values($errors),
            'conflicts' => $conflicts->map(fn (Booking $b) => [
                'time' => $b->timeLabel(),
                'status' => $b->status->label(),
            ])->values()->all(),
        ];
    }

    public function create(
        User $user, 
        Room $room, 
        Carbon $start, 
        Carbon $end, 
        ?string $notes = null, 
        string $activityType = 'non_usk'
    ): Booking
    {
        $booking = DB::transaction(function () use ($user, $room, $start, $end, $notes, $activityType) {
            // Kunci baris ruangan: pengajuan paralel untuk ruangan yang sama diproses bergantian,
            // sehingga pengecekan bentrok + insert bersifat atomik.
            $room = Room::with('building')->whereKey($room->getKey())->lockForUpdate()->first()
                ?? throw ValidationException::withMessages(['room_id' => 'Ruangan tidak ditemukan.']);

            if ($errors = $this->ruleErrors($room, $start, $end)) {
                throw ValidationException::withMessages($errors);
            }
            $this->assertNoConflict($room, $start, $end);

            // Tentukan harga final secara otomatis: jika USK maka 0, jika Non-USK ambil harga dari ruangan
            $finalPrice = ($activityType === 'usk') ? 0 : ($room->price ?? 0);

            $booking = new Booking([
                'notes' => $notes,
                'activity_type' => $activityType,
                'final_price' => $finalPrice,
            ]);
            $booking->booking_number = $this->nextBookingNumber();
            $booking->user_id = $user->getKey();
            $booking->room_id = $room->getKey();
            $booking->period_id = Period::current()->getKey();
            $booking->start_at = $start;
            $booking->end_at = $end;
            $booking->status = BookingStatus::Pending;
            $booking->save();

            $this->logger->log('CREATE_BOOKING', $booking, "Mengajukan peminjaman {$room->name} ({$booking->dateLabel()} {$booking->timeLabel()})", actor: $user);

            return $booking;
        });

        $this->notifyAdmins(new BookingSubmitted($booking));

        return $booking;
    }

    public function approve(Booking $booking, User $admin): Booking
    {
        $booking = DB::transaction(function () use ($booking, $admin) {
            $booking = Booking::whereKey($booking->getKey())->lockForUpdate()->firstOrFail();
            if ($booking->status !== BookingStatus::Pending) {
                throw ValidationException::withMessages(['booking' => 'Hanya pengajuan berstatus Menunggu yang dapat disetujui.']);
            }

            $room = Room::with('building')->withTrashed()->whereKey($booking->room_id)->lockForUpdate()->firstOrFail();
            if (! $room->isBookable()) {
                throw ValidationException::withMessages(['booking' => 'Ruangan atau gedung sedang tidak aktif. Pengajuan tidak dapat disetujui.']);
            }
            if ($booking->start_at->lte(now())) {
                throw ValidationException::withMessages(['booking' => 'Waktu peminjaman sudah lewat. Pengajuan tidak dapat disetujui.']);
            }
            // Invariant utama: tidak boleh ada dua booking APPROVED yang overlap pada ruangan yang sama.
            $this->assertNoConflict($room, $booking->start_at, $booking->end_at, includePending: false, ignoreId: $booking->id);

            $booking->status = BookingStatus::Approved;
            $booking->approved_by = $admin->getKey();
            $booking->approved_at = now();
            $booking->verification_token = Str::random(48);
            $booking->save();

            $this->logger->log('APPROVE_BOOKING', $booking, "Menyetujui peminjaman {$booking->booking_number}", actor: $admin);

            return $booking;
        });

        $this->notifyUser($booking, new BookingApproved($booking));

        return $booking;
    }

    public function reject(Booking $booking, User $admin, string $reason): Booking
    {
        $booking = DB::transaction(function () use ($booking, $admin, $reason) {
            $booking = Booking::whereKey($booking->getKey())->lockForUpdate()->firstOrFail();
            if ($booking->status !== BookingStatus::Pending) {
                throw ValidationException::withMessages(['booking' => 'Hanya pengajuan berstatus Menunggu yang dapat ditolak.']);
            }

            $booking->status = BookingStatus::Rejected;
            $booking->rejection_reason = $reason;
            $booking->approved_by = $admin->getKey();
            $booking->approved_at = now();
            $booking->save();

            $this->logger->log('REJECT_BOOKING', $booking, "Menolak peminjaman {$booking->booking_number}", ['reason' => $reason], $admin);

            return $booking;
        });

        $this->notifyUser($booking, new BookingRejected($booking));

        return $booking;
    }

    public function cancel(Booking $booking, User $actor, ?string $reason = null): Booking
    {
        $booking = DB::transaction(function () use ($booking, $actor, $reason) {
            $booking = Booking::whereKey($booking->getKey())->lockForUpdate()->firstOrFail();
            if (! $booking->status->isCancellable()) {
                throw ValidationException::withMessages(['booking' => 'Peminjaman ini tidak dapat dibatalkan.']);
            }
            if (! $actor->isAdmin() && $booking->end_at->lte(now())) {
                throw ValidationException::withMessages(['booking' => 'Peminjaman yang sudah berakhir tidak dapat dibatalkan.']);
            }

            $booking->status = BookingStatus::Cancelled;
            $booking->cancelled_at = now();
            $booking->cancelled_by = $actor->getKey();
            $booking->cancellation_reason = $reason;
            $booking->save();

            $this->logger->log('CANCEL_BOOKING', $booking, "Membatalkan peminjaman {$booking->booking_number}", array_filter(['reason' => $reason]), $actor);

            return $booking;
        });

        if ($actor->isAdmin() && $actor->isNot($booking->user)) {
            $this->notifyUser($booking, new BookingCancelled($booking));
        }

        return $booking;
    }

    /**
     * Dipanggil scheduler: approved yang sudah berakhir → completed,
     * pending yang waktu mulainya sudah lewat → rejected (kedaluwarsa).
     *
     * @return array{completed:int, expired:int}
     */
    public function closeFinished(): array
    {
        $completed = Booking::status(BookingStatus::Approved)->where('end_at', '<=', now())
            ->update(['status' => BookingStatus::Completed->value, 'completed_at' => now(), 'updated_at' => now()]);

        $expired = Booking::status(BookingStatus::Pending)->where('start_at', '<=', now())
            ->update([
                'status' => BookingStatus::Rejected->value,
                'rejection_reason' => 'Kedaluwarsa: pengajuan belum diproses hingga waktu peminjaman dimulai.',
                'updated_at' => now(),
            ]);

        return ['completed' => $completed, 'expired' => $expired];
    }

    private function assertNoConflict(Room $room, Carbon $start, Carbon $end, ?bool $includePending = null, ?int $ignoreId = null): void
    {
        $conflicts = $this->conflicts($room, $start, $end, $includePending, $ignoreId);
        if ($conflicts->isNotEmpty()) {
            $times = $conflicts->map(fn (Booking $b) => $b->timeLabel())->implode(', ');
            throw ValidationException::withMessages([
                'start_time' => "Ruangan tidak tersedia pada waktu tersebut. Digunakan: {$times}.",
            ]);
        }
    }

    /** PMJ-YYYYMMDD-NNNN, nomor urut harian atomik via tabel booking_counters. */
    private function nextBookingNumber(): string
    {
        $date = now()->toDateString();
        DB::table('booking_counters')->insertOrIgnore(['date' => $date, 'last_number' => 0]);
        $current = DB::table('booking_counters')->where('date', $date)->lockForUpdate()->value('last_number');
        $next = $current + 1;
        DB::table('booking_counters')->where('date', $date)->update(['last_number' => $next]);

        return sprintf('%s-%s-%04d', config('booking.number_prefix'), now()->format('Ymd'), $next);
    }

    private function notifyAdmins(BaseNotification $notification): void
    {
        $recipients = $this->mailSettings->adminRecipients();
        if ($recipients === []) {
            return;
        }

        $this->safely(fn () => Notification::route('mail', $recipients)->notify($notification));
    }

    private function notifyUser(Booking $booking, BaseNotification $notification): void
    {
        $this->safely(fn () => $booking->user?->notify($notification));
    }

    /** Kegagalan email tidak boleh membatalkan transaksi peminjaman yang sudah tersimpan. */
    private function safely(callable $callback): void
    {
        try {
            $callback();
        } catch (Throwable $e) {
            report($e);
        }
    }
}