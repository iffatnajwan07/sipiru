@props(['label', 'value', 'icon', 'tone' => 'indigo', 'href' => null])
@php
    $tones = ['indigo' => 'bg-indigo-50 text-indigo-600', 'green' => 'bg-emerald-50 text-emerald-600', 'amber' => 'bg-amber-50 text-amber-600', 'red' => 'bg-red-50 text-red-600', 'slate' => 'bg-slate-100 text-slate-600', 'blue' => 'bg-sky-50 text-sky-600'];
@endphp
<{{ $href ? 'a' : 'div' }} @if($href) href="{{ $href }}" @endif class="card flex items-center gap-4 p-4 {{ $href ? 'transition hover:border-indigo-200 hover:shadow-sm' : '' }}">
    <div class="grid size-11 place-items-center rounded-lg {{ $tones[$tone] ?? $tones['indigo'] }}"><x-icon :name="$icon" class="size-5" /></div>
    <div class="min-w-0 flex-1">
        <div class="text-2xl font-semibold tabular-nums text-slate-900">{{ $value }}</div>
        <div class="text-xs leading-tight font-medium text-slate-500">{{ $label }}</div>
    </div>
</{{ $href ? 'a' : 'div' }}>
