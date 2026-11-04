<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\BuildingRequest;
use App\Models\ActivityLog;
use App\Models\Building;
use App\Services\BuildingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BuildingController extends Controller
{
    public function __construct(private BuildingService $service) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Building::class);

        $buildings = Building::query()
            ->withCount('rooms')
            ->search($request->q)
            ->when($request->status === 'active', fn ($q) => $q->where('is_active', true))
            ->when($request->status === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.buildings.index', compact('buildings'));
    }

    public function create(): View
    {
        $this->authorize('create', Building::class);

        return view('admin.buildings.form', ['building' => new Building(['is_active' => true])]);
    }

    public function store(BuildingRequest $request): RedirectResponse
    {
        $building = $this->service->create($request->validated());

        return redirect()->route('admin.buildings.show', $building)->with('success', "Gedung {$building->name} berhasil ditambahkan.");
    }

    public function show(Building $building): View
    {
        $this->authorize('view', $building);

        return view('admin.buildings.show', [
            'building' => $building,
            'rooms' => $building->rooms()->withCount('bookings')->orderBy('name')->get(),
            'logs' => ActivityLog::with('user')->where('subject_type', $building->getMorphClass())->where('subject_id', $building->id)->latest('id')->limit(20)->get(),
        ]);
    }

    public function edit(Building $building): View
    {
        $this->authorize('update', $building);

        return view('admin.buildings.form', compact('building'));
    }

    public function update(BuildingRequest $request, Building $building): RedirectResponse
    {
        $this->service->update($building, $request->validated());

        return redirect()->route('admin.buildings.show', $building)->with('success', 'Gedung berhasil diperbarui.');
    }

    public function toggle(Building $building): RedirectResponse
    {
        $this->authorize('update', $building);
        $this->service->setActive($building, ! $building->is_active);

        return back()->with('success', $building->is_active ? 'Gedung diaktifkan.' : 'Gedung dinonaktifkan. Ruangan di gedung ini tidak dapat dipinjam.');
    }

    public function destroy(Building $building): RedirectResponse
    {
        $this->authorize('delete', $building);
        $this->service->delete($building);

        return redirect()->route('admin.buildings.index')->with('success', 'Gedung dihapus.');
    }

    public function checkDuplicate(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Building::class);
        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:150'],
            'code' => ['nullable', 'string', 'max:30'],
            'ignore' => ['nullable', 'integer'],
        ]);

        return response()->json($this->service->checkDuplicate($data['name'] ?? null, $data['code'] ?? null, $data['ignore'] ?? null));
    }
}
