<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Period;
use App\Services\PeriodService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PeriodController extends Controller
{
    public function __construct(private PeriodService $service) {}

    public function index(): View
    {
        $this->authorize('viewAny', Period::class);

        return view('admin.periods.index', [
            'periods' => Period::withCount('bookings')->orderByDesc('year')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Period::class);
        $data = $request->validate([
            'year' => ['required', 'integer', 'min:2000', 'max:2100', 'unique:periods,year'],
            'activate' => ['boolean'],
        ], ['year.unique' => 'Periode tahun tersebut sudah ada.'], ['year' => 'tahun']);

        $this->service->create((int) $data['year'], $request->boolean('activate'));

        return back()->with('success', "Periode {$data['year']} ditambahkan.");
    }

    public function activate(Period $period): RedirectResponse
    {
        $this->authorize('update', $period);
        $this->service->activate($period);

        return back()->with('success', "Periode {$period->year} sekarang aktif. Periode lain otomatis dinonaktifkan.");
    }

    public function deactivate(Period $period): RedirectResponse
    {
        $this->authorize('update', $period);
        $this->service->deactivate($period);

        return back()->with('success', "Periode {$period->year} dinonaktifkan. Pengajuan baru tidak dapat dibuat sampai ada periode aktif.");
    }
}
