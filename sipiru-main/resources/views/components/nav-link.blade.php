@props(['href', 'active' => false, 'icon', 'count' => null])
<a href="{{ $href }}" @class(['nav-link', 'nav-link-active' => $active]) @if($active) aria-current="page" @endif>
    <x-icon :name="$icon" class="size-4.5" />
    <span class="flex-1 truncate">{{ $slot }}</span>
    @if ($count)
        <span class="rounded-full bg-amber-100 px-1.5 text-[11px] font-semibold text-amber-700">{{ $count }}</span>
    @endif
</a>
