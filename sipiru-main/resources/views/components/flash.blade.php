@props(['inline' => false])

@php
    $alerts = [
        'success' => ['circle-check', 'border-emerald-200 bg-emerald-50 text-emerald-800'],
        'error'   => ['circle-x', 'border-red-200 bg-red-50 text-red-800'],
        'status'  => ['info', 'border-sky-200 bg-sky-50 text-sky-800']
    ];
@endphp

@foreach ($alerts as $key => $data)
    @if (session($key))
        <div x-data="{ show: true }" x-show="show" class="mb-5 flex items-start gap-3 rounded-lg border px-4 py-3 text-sm {{ $data[1] }}" role="alert">
            <x-icon :name="$data[0]" class="mt-0.5 size-4" />
            <div class="flex-1">{{ session($key) }}</div>
            <button type="button" @click="show = false" class="opacity-60 hover:opacity-100" aria-label="Tutup"><x-icon name="x" class="size-4" /></button>
        </div>
    @endif
@endforeach

@if ($errors->any())
    @php($generic = collect($errors->getMessages())->only(['booking', 'building', 'room', 'login', 'start_time', 'end_time', 'date', 'room_id'])->flatten())
    @if ($generic->isNotEmpty() && ! $inline)
        <div class="mb-5 flex items-start gap-3 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
            <x-icon name="triangle-alert" class="mt-0.5 size-4" />
            <ul class="space-y-0.5">
                @foreach ($generic as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    @endif
@endif