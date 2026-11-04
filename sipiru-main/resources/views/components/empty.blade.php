@props(['message' => 'Belum ada data.', 'icon' => 'inbox'])
<div class="flex flex-col items-center justify-center px-6 py-12 text-center">
    <div class="mb-3 grid size-12 place-items-center rounded-full bg-slate-100 text-slate-400"><x-icon :name="$icon" class="size-6" /></div>
    <p class="text-sm text-slate-500">{{ $message }}</p>
    @if (! $slot->isEmpty())<div class="mt-4">{{ $slot }}</div>@endif
</div>
