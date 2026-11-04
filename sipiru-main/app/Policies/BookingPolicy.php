<?php

namespace App\Policies;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /** IDOR protection: user hanya dapat melihat booking miliknya. */
    public function view(User $user, Booking $booking): bool
    {
        return $user->isAdmin() || $booking->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return ! $user->isAdmin() && $user->is_active;
    }

    public function cancel(User $user, Booking $booking): bool
    {
        if (! $booking->status->isCancellable()) {
            return false;
        }

        return $user->isAdmin() || ($booking->user_id === $user->id && $booking->end_at->isFuture());
    }

    public function approve(User $user, Booking $booking): bool
    {
        return $user->isAdmin() && $booking->status === BookingStatus::Pending;
    }

    public function reject(User $user, Booking $booking): bool
    {
        return $this->approve($user, $booking);
    }

    public function downloadProof(User $user, Booking $booking): bool
    {
        return $this->view($user, $booking) && $booking->isApproved();
    }
}
