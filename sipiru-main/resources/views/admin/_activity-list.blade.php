{{-- Daftar log aktivitas ringkas + before/after --}}
<ul class="divide-y divide-slate-100">
    @forelse ($logs as $log)
        <li class="px-5 py-3 text-sm">
            <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                <span class="rounded bg-slate-100 px-1.5 py-0.5 font-mono text-[11px] font-semibold text-slate-700">{{ $log->action }}</span>
                <span class="text-slate-700">{{ $log->user?->name ?? 'Sistem' }}</span>
                <span class="ml-auto text-xs text-slate-400">{{ $log->created_at->translatedFormat('d M Y H:i') }}</span>
            </div>
            @if ($log->description)<div class="mt-1 text-slate-500">{{ $log->description }}</div>@endif
            @if (isset($log->properties['before']) || isset($log->properties['after']))
                <div class="mt-2 overflow-x-auto rounded-md bg-slate-50 p-2 font-mono text-xs">
                    @foreach (($log->properties['after'] ?? []) as $field => $value)
                        <div><span class="text-slate-500">{{ $field }}:</span>
                            @if (array_key_exists($field, $log->properties['before'] ?? []))<span class="text-red-600 line-through">{{ json_encode($log->properties['before'][$field], JSON_UNESCAPED_UNICODE) }}</span> →@endif
                            <span class="text-emerald-700">{{ json_encode($value, JSON_UNESCAPED_UNICODE) }}</span></div>
                    @endforeach
                </div>
            @elseif (! empty($log->properties))
                <div class="mt-2 overflow-x-auto rounded-md bg-slate-50 p-2 font-mono text-xs text-slate-600">{{ json_encode($log->properties, JSON_UNESCAPED_UNICODE) }}</div>
            @endif
        </li>
    @empty
        <li><x-empty message="Belum ada aktivitas." icon="history" /></li>
    @endforelse
</ul>
