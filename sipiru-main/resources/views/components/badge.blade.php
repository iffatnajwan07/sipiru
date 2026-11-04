@props(['tone' => 'slate'])
@php
    $tones = [
        'slate' => 'bg-slate-100 text-slate-700 ring-slate-200',
        'green' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'amber' => 'bg-amber-50 text-amber-700 ring-amber-200',
        'red' => 'bg-red-50 text-red-700 ring-red-200',
        'indigo' => 'bg-indigo-50 text-indigo-700 ring-indigo-200',
        'blue' => 'bg-sky-50 text-sky-700 ring-sky-200',
    ];
@endphp
<span {{ $attributes->class(['inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset', $tones[$tone] ?? $tones['slate']]) }}>{{ $slot }}</span>
