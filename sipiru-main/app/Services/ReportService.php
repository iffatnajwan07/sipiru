<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ReportService
{
    public function query(array $filters): Builder
    {
        return Booking::query()
            ->with(['room.building', 'user'])
            ->when($filters['period'] ?? null, fn ($q, $id) => $q->where('period_id', $id))
            ->when($filters['building'] ?? null, fn ($q, $id) => $q->whereHas('room', fn ($r) => $r->where('building_id', $id)))
            ->when($filters['room'] ?? null, fn ($q, $id) => $q->where('room_id', $id))
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filters['user'] ?? null, fn ($q, $id) => $q->where('user_id', $id))
            ->when($filters['from'] ?? null, fn ($q, $d) => $q->whereDate('start_at', '>=', $d))
            ->when($filters['to'] ?? null, fn ($q, $d) => $q->whereDate('start_at', '<=', $d));
    }

    public function summary(array $filters): array
    {
        $counts = $this->query($filters)->reorder()->toBase()
            ->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return [
            'total' => (int) $counts->sum(),
            'approved' => (int) ($counts[BookingStatus::Approved->value] ?? 0) + (int) ($counts[BookingStatus::Completed->value] ?? 0),
            'pending' => (int) ($counts[BookingStatus::Pending->value] ?? 0),
            'rejected' => (int) ($counts[BookingStatus::Rejected->value] ?? 0),
            'cancelled' => (int) ($counts[BookingStatus::Cancelled->value] ?? 0),
        ];
    }

    /** Pemakaian ruangan: jumlah & total jam booking yang disetujui/selesai. */
    public function roomUsage(array $filters): Collection
    {
        return $this->query(array_merge($filters, ['status' => null]))
            ->status(BookingStatus::Approved, BookingStatus::Completed)
            ->get()
            ->groupBy('room_id')
            ->map(fn (Collection $items) => [
                'room' => $items->first()->room->name,
                'code' => $items->first()->room->code,
                'building' => $items->first()->room->building->name,
                'count' => $items->count(),
                'hours' => round($items->sum(fn (Booking $b) => $b->start_at->diffInMinutes($b->end_at)) / 60, 1),
            ])
            ->sortByDesc('hours')
            ->values();
    }
}
