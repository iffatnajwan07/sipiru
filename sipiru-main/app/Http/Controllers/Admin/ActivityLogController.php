<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\LoginLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', ActivityLog::class);
        $request->validate(['date' => ['nullable', 'date_format:Y-m-d']]);

        $logs = ActivityLog::with('user')
            ->when($request->action, fn ($q, $a) => $q->where('action', $a))
            ->when($request->q, fn ($q, $t) => $q->where(fn ($w) => $w->where('subject_label', 'like', '%'.addcslashes($t, '%_').'%')
                ->orWhere('description', 'like', '%'.addcslashes($t, '%_').'%')))
            ->when($request->date, fn ($q, $d) => $q->whereDate('created_at', $d))
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('admin.logs.activity', [
            'logs' => $logs,
            'actions' => ActivityLog::distinct()->orderBy('action')->pluck('action'),
        ]);
    }

    public function logins(Request $request): View
    {
        $this->authorize('viewAny', ActivityLog::class);

        $logs = LoginLog::with('user')
            ->when($request->q, fn ($q, $t) => $q->whereHas('user', fn ($u) => $u->where('name', 'like', '%'.addcslashes($t, '%_').'%')
                ->orWhere('email', 'like', '%'.addcslashes($t, '%_').'%')))
            ->latest('login_at')
            ->paginate(25)
            ->withQueryString();

        return view('admin.logs.logins', compact('logs'));
    }
}
