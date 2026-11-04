<?php

namespace App\Models;

use App\Enums\BookingStatus;
use Database\Factories\BookingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property Carbon $start_at
 * @property Carbon $end_at
 * @property BookingStatus $status
 * @property string $activity_type
 * @property float $final_price
 */
#[Fillable(['notes', 'activity_type', 'final_price'])]
#[Hidden(['verification_token'])]
class Booking extends Model
{
    /** @use HasFactory<BookingFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'approved_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'completed_at' => 'datetime',
            'reminder_day_sent_at' => 'datetime',
            'reminder_soon_sent_at' => 'datetime',
            'status' => BookingStatus::class,
            'final_price' => 'decimal:2', // Casting otomatis untuk nominal uang
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class)->withTrashed();
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(Period::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by')->withTrashed();
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by')->withTrashed();
    }

    /**
     * Dua rentang bentrok jika: new_start < existing_end AND new_end > existing_start.
     * Rentang yang bersebelahan (11:00 selesai, 11:00 mulai) TIDAK bentrok.
     */
    public function scopeOverlapping(Builder $query, $start, $end): void
    {
        $query->where('start_at', '<', $end)->where('end_at', '>', $start);
    }

    public function scopeStatus(Builder $query, BookingStatus|string ...$statuses): void
    {
        $query->whereIn('status', array_map(
            fn ($s) => $s instanceof BookingStatus ? $s->value : $s,
            $statuses,
        ));
    }

    public function isApproved(): bool
    {
        return in_array($this->status, [BookingStatus::Approved, BookingStatus::Completed], true);
    }

    public function dateLabel(): string
    {
        return $this->start_at->translatedFormat('l, d F Y');
    }

    public function timeLabel(): string
    {
        return $this->start_at->format('H:i').' - '.$this->end_at->format('H:i');
    }

    public function verificationUrl(): ?string
    {
        return $this->verification_token ? route('bookings.verify', $this->verification_token) : null;
    }

    /** Link "Add to Google Calendar" (tanpa OAuth, event template). */
    public function googleCalendarUrl(): string
    {
        $fmt = fn (Carbon $d) => $d->copy()->utc()->format('Ymd\THis\Z');

        return 'https://calendar.google.com/calendar/render?'.http_build_query([
            'action' => 'TEMPLATE',
            'text' => 'Peminjaman '.$this->room->name.' ('.$this->booking_number.')',
            'dates' => $fmt($this->start_at).'/'.$fmt($this->end_at),
            'details' => 'Nomor peminjaman: '.$this->booking_number."\n".($this->verificationUrl() ?? ''),
            'location' => $this->room->name.', '.$this->room->building->name,
        ]);
    }
}