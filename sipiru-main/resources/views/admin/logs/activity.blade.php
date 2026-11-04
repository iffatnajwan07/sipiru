<x-layouts.app title="Activity Log">
    <x-page-header title="Activity Log" subtitle="Catatan tindakan penting di sistem." />
    <div class="card">
        <form method="GET" class="flex flex-col gap-3 border-b border-slate-100 p-4 sm:flex-row">
            <input name="q" value="{{ request('q') }}" placeholder="Cari subjek / deskripsi…" class="input flex-1">
            <select name="action" class="input sm:w-56">
                <option value="">Semua aksi</option>
                @foreach ($actions as $a)<option value="{{ $a }}" @selected(request('action') === $a)>{{ $a }}</option>@endforeach
            </select>
            <input type="date" name="date" value="{{ request('date') }}" class="input sm:w-44">
            <button class="btn btn-secondary"><x-icon name="funnel" class="size-4" /> Filter</button>
        </form>
        <div class="overflow-x-auto">
            <table class="table">
                <thead><tr><th>Waktu</th><th>User</th><th>Aksi</th><th>Subjek</th><th>Detail</th><th>IP</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($logs as $log)
                        <tr class="align-top">
                            <td class="whitespace-nowrap text-slate-500">{{ $log->created_at->translatedFormat('d M Y H:i:s') }}</td>
                            <td>{{ $log->user?->name ?? 'Sistem' }}@if($log->user)<div class="font-mono text-xs text-slate-400">{{ $log->user->identifier() ?? $log->user->email }}</div>@endif</td>
                            <td><span class="rounded bg-slate-100 px-1.5 py-0.5 font-mono text-[11px] font-semibold">{{ $log->action }}</span></td>
                            <td>{{ $log->subject_label ?? '-' }}@if($log->subject_type)<div class="text-xs text-slate-400">{{ class_basename($log->subject_type) }}</div>@endif</td>
                            <td class="max-w-md">
                                <div class="text-slate-600">{{ $log->description }}</div>
                                @if (isset($log->properties['after']))
                                    <div class="mt-1 font-mono text-xs">
                                        @foreach ($log->properties['after'] as $field => $value)
                                            <div><span class="text-slate-400">{{ $field }}:</span>
                                                @if (array_key_exists($field, $log->properties['before'] ?? []))<span class="text-red-600">{{ json_encode($log->properties['before'][$field], JSON_UNESCAPED_UNICODE) }}</span> → @endif
                                                <span class="text-emerald-700">{{ json_encode($value, JSON_UNESCAPED_UNICODE) }}</span></div>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                            <td class="font-mono text-xs text-slate-500">{{ $log->ip_address ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><x-empty message="Belum ada aktivitas." icon="history" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 px-4 py-3">{{ $logs->links() }}</div>
    </div>
</x-layouts.app>
