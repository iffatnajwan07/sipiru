<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Tujuan QR permanen ruangan: langsung ke form pengajuan dengan ruangan terpilih. */
class RoomQrController extends Controller
{
    public function __invoke(Request $request, string $token): RedirectResponse
    {
        $room = Room::with('building')->where('public_token', $token)->firstOrFail();

        if ($request->user()->isAdmin()) {
            return redirect()->route('admin.rooms.show', $room);
        }

        if (! $room->isBookable()) {
            return redirect()->route('bookings.create')
                ->with('error', "Ruangan {$room->name} sedang tidak dapat dipinjam.");
        }

        return redirect()->route('bookings.create', ['room' => $room->id]);
    }
}
