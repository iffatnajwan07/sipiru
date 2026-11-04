<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\RoomRequest;
use App\Models\ActivityLog;
use App\Models\Building;
use App\Models\Room;
use App\Services\RoomService;
use App\Support\QrCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class RoomController extends Controller
{
    public function __construct(
        private readonly RoomService $service
    ) {
    }

    /**
     * Menampilkan daftar ruangan.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Room::class);

        $rooms = Room::query()
            ->with('building')
            ->search($request->input('q'))
            ->when(
                $request->filled('building'),
                fn ($query) => $query->where(
                    'building_id',
                    $request->input('building')
                )
            )
            ->when(
                $request->filled('floor'),
                fn ($query) => $query->where(
                    'floor',
                    $request->input('floor')
                )
            )
            ->when(
                $request->filled('min_capacity'),
                fn ($query) => $query->where(
                    'capacity',
                    '>=',
                    (int) $request->input('min_capacity')
                )
            )
            ->when(
                $request->input('status') === 'active',
                fn ($query) => $query->where('is_active', true)
            )
            ->when(
                $request->input('status') === 'inactive',
                fn ($query) => $query->where('is_active', false)
            )
            ->orderBy(
                Building::select('name')
                    ->whereColumn('buildings.id', 'rooms.building_id')
            )
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.rooms.index', [
            'rooms' => $rooms,

            'buildings' => Building::query()
                ->orderBy('name')
                ->get(['id', 'name']),

            'floors' => Room::query()
                ->whereNotNull('floor')
                ->where('floor', '!=', '')
                ->distinct()
                ->orderBy('floor')
                ->pluck('floor'),
        ]);
    }

    /**
     * Menampilkan formulir tambah ruangan.
     */
    public function create(Request $request): View
    {
        $this->authorize('create', Room::class);

        $room = new Room([
            'is_active' => true,
            'building_id' => $request->integer('building') ?: null,
        ]);

        return view('admin.rooms.form', [
            'room' => $room,

            'buildings' => Building::query()
                ->orderBy('name')
                ->get(['id', 'name', 'code', 'is_active']),
        ]);
    }

    /**
     * Menyimpan ruangan baru.
     */
    public function store(RoomRequest $request): RedirectResponse
    {
        $room = $this->service->create(
            $request->validated()
        );

        return redirect()
            ->route('admin.rooms.show', $room)
            ->with(
                'success',
                "Ruangan {$room->name} berhasil ditambahkan."
            );
    }

    /**
     * Menampilkan detail ruangan.
     */
    public function show(Room $room): View
    {
        $this->authorize('view', $room);

        $room->load('building');

        return view('admin.rooms.show', [
            'room' => $room,

            'qrSvg' => QrCode::svg(
                route('rooms.qr-booking', $room->public_token)
            ),

            'bookings' => $room->bookings()
                ->with('user')
                ->latest('start_at')
                ->limit(10)
                ->get(),

            'logs' => ActivityLog::query()
                ->with('user')
                ->where(
                    'subject_type',
                    $room->getMorphClass()
                )
                ->where('subject_id', $room->id)
                ->latest('id')
                ->limit(20)
                ->get(),
        ]);
    }

    /**
     * Menampilkan formulir edit ruangan.
     */
    public function edit(Room $room): View
    {
        $this->authorize('update', $room);

        return view('admin.rooms.form', [
            'room' => $room,

            'buildings' => Building::query()
                ->orderBy('name')
                ->get(['id', 'name', 'code', 'is_active']),
        ]);
    }

    /**
     * Memperbarui data ruangan.
     */
    public function update(
        RoomRequest $request,
        Room $room
    ): RedirectResponse {
        $this->service->update(
            $room,
            $request->validated()
        );

        return redirect()
            ->route('admin.rooms.show', $room)
            ->with(
                'success',
                'Ruangan berhasil diperbarui.'
            );
    }

    /**
     * Mengaktifkan atau menonaktifkan ruangan.
     */
    public function toggle(Room $room): RedirectResponse
    {
        $this->authorize('update', $room);

        $this->service->setActive(
            $room,
            ! $room->is_active
        );

        return back()->with(
            'success',
            $room->is_active
                ? 'Ruangan berhasil diaktifkan.'
                : 'Ruangan berhasil dinonaktifkan.'
        );
    }

    /**
     * Menghapus ruangan.
     */
    public function destroy(Room $room): RedirectResponse
    {
        $this->authorize('delete', $room);

        $this->service->delete($room);

        return redirect()
            ->route('admin.rooms.index')
            ->with('success', 'Ruangan berhasil dihapus.');
    }

    /**
     * Menampilkan halaman QR ruangan siap cetak.
     */
    public function qr(Room $room): View
    {
        $this->authorize('view', $room);

        return view('admin.rooms.qr', [
            'room' => $room->load('building'),

            'qrSvg' => QrCode::svg(
                route('rooms.qr-booking', $room->public_token)
            ),
        ]);
    }

    /**
     * Mengunduh QR ruangan dalam format PNG.
     */
    public function qrPng(Room $room): Response
    {
        $this->authorize('view', $room);

        return response(
            QrCode::png(
                route('rooms.qr-booking', $room->public_token)
            ),
            200,
            [
                'Content-Type' => 'image/png',
                'Content-Disposition' =>
                    'attachment; filename="qr-'
                    . $room->code
                    . '.png"',
            ]
        );
    }

    /**
     * Memeriksa duplikasi nama dan kode ruangan.
     */
    public function checkDuplicate(
        Request $request
    ): JsonResponse {
        $this->authorize('viewAny', Room::class);

        $data = $request->validate([
            'building_id' => [
                'nullable',
                'integer',
                'exists:buildings,id',
            ],

            'name' => [
                'nullable',
                'string',
                'max:150',
            ],

            'code' => [
                'nullable',
                'string',
                'max:40',
            ],

            'ignore' => [
                'nullable',
                'integer',
                'exists:rooms,id',
            ],
        ]);

        $result = $this->service->checkDuplicate(
            $data['building_id'] ?? null,
            $data['name'] ?? null,
            $data['code'] ?? null,
            $data['ignore'] ?? null
        );

        return response()->json($result);
    }
}