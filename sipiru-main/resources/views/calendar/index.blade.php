<x-layouts.app title="Kalender">
    @php
        $prev = $month->copy()->subMonth()->format('Y-m');
        $next = $month->copy()->addMonth()->format('Y-m');
        $filters = request()->only('building', 'room');
        $dotTone = fn ($status) => match ($status) {
            \App\Enums\BookingStatus::Approved => 'bg-emerald-500',
            \App\Enums\BookingStatus::Pending => 'bg-amber-500',
            default => 'bg-slate-400',
        };
    @endphp
    <x-page-header title="Kalender Peminjaman" :subtitle="$isAdmin ? 'Seluruh peminjaman yang menunggu, disetujui, dan selesai.' : 'Lihat ketersediaan ruangan. Slot terisi ditandai tanpa identitas peminjam lain.'" />

    <div class="card">
        <div class="flex flex-col gap-3 border-b border-slate-100 p-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-center gap-2">
                <a href="{{ route($routeName, array_merge($filters, ['month' => $prev])) }}" class="btn btn-secondary btn-sm" aria-label="Bulan sebelumnya"><x-icon name="chevron-left" class="size-4" /></a>
                <h2 class="min-w-40 text-center text-base font-semibold">{{ $month->translatedFormat('F Y') }}</h2>
                <a href="{{ route($routeName, array_merge($filters, ['month' => $next])) }}" class="btn btn-secondary btn-sm" aria-label="Bulan berikutnya"><x-icon name="chevron-right" class="size-4" /></a>
                <a href="{{ route($routeName, $filters) }}" class="btn btn-ghost btn-sm">Hari ini</a>
            </div>
            <form method="GET" class="flex flex-col gap-2 sm:flex-row">
                <input type="hidden" name="month" value="{{ $month->format('Y-m') }}">
                <select name="building" class="input sm:w-48" onchange="this.form.room.value=''; this.form.submit()">
                    <option value="">Semua gedung</option>
                    @foreach ($buildings as $b)<option value="{{ $b->id }}" @selected(request('building') == $b->id)>{{ $b->name }}</option>@endforeach
                </select>
                <select name="room" class="input sm:w-48" onchange="this.form.submit()">
                    <option value="">Semua ruangan</option>
                    @foreach ($rooms as $r)<option value="{{ $r->id }}" @selected(request('room') == $r->id)>{{ $r->name }}</option>@endforeach
                </select>
            </form>
        </div>

        {{-- Grid (tablet/desktop) --}}
        <div class="hidden md:block">
            <div class="grid grid-cols-7 border-b border-slate-100 bg-slate-50 text-center text-xs font-semibold uppercase tracking-wide text-slate-500">
                @foreach (['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'] as $d)<div class="py-2">{{ $d }}</div>@endforeach
            </div>
            <div class="grid grid-cols-7">
                @for ($day = $gridStart->copy(); $day->lte($gridEnd); $day->addDay())
                    @php($items = $bookings->get($day->toDateString(), collect()))
                    <div @class(['min-h-28 border-b border-r border-slate-100 p-1.5 text-xs', 'bg-slate-50/60 text-slate-400' => ! $day->isSameMonth($month)])>
                        <div @class(['mb-1 grid size-6 place-items-center rounded-full text-xs font-medium', 'bg-indigo-600 text-white' => $day->isToday()])>{{ $day->day }}</div>
                        <div class="space-y-1">
                            @foreach ($items->take(3) as $item)
                                <a @if ($item['url']) href="{{ $item['url'] }}" @endif @class(['block truncate rounded px-1.5 py-1', 'bg-indigo-50 text-indigo-800 ring-1 ring-indigo-200' => $item['mine'], 'bg-slate-100 text-slate-700 hover:bg-slate-200' => ! $item['mine']]) title="{{ $item['time'] }} · {{ $item['room'] }} ({{ $item['building'] }}){{ $item['title'] ? ' · '.$item['title'] : '' }} · {{ $item['status']->label() }}">
                                    <span class="mr-1 inline-block size-1.5 rounded-full {{ $dotTone($item['status']) }}"></span><span class="font-medium">{{ \Illuminate\Support\Str::before($item['time'], ' -') }}</span> {{ $item['room'] }}
                                </a>
                            @endforeach
                            @if ($items->count() > 3)<div class="px-1.5 text-slate-500">+{{ $items->count() - 3 }} lainnya</div>@endif
                        </div>
                    </div>
                @endfor
            </div>
        </div>

        {{-- Agenda (mobile) --}}
        <div class="divide-y divide-slate-100 md:hidden">
            @php($monthItems = $bookings->filter(fn ($v, $date) => \Illuminate\Support\Carbon::parse($date)->isSameMonth($month)))
            @forelse ($monthItems as $date => $items)
                <div class="p-4">
                    <div class="mb-2 text-sm font-semibold">{{ \Illuminate\Support\Carbon::parse($date)->translatedFormat('l, d F') }}</div>
                    <ul class="space-y-2">
                        @foreach ($items as $item)
                            <li class="flex items-center gap-3 rounded-lg border border-slate-200 px-3 py-2 text-sm">
                                <span class="size-2 rounded-full {{ $dotTone($item['status']) }}"></span>
                                <div class="min-w-0 flex-1">
                                    <div class="font-medium">{{ $item['time'] }} · {{ $item['room'] }}</div>
                                    <div class="truncate text-xs text-slate-500">{{ $item['building'] }}{{ $item['title'] ? ' · '.$item['title'] : '' }}</div>
                                </div>
                                @if ($item['url'])<a href="{{ $item['url'] }}" class="text-indigo-600"><x-icon name="chevron-right" class="size-4" /></a>@endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @empty
                <x-empty message="Tidak ada peminjaman pada bulan ini." icon="calendar-days" />
            @endforelse
        </div>

        <div class="flex flex-wrap items-center gap-4 border-t border-slate-100 px-4 py-3 text-xs text-slate-500">
            <span class="flex items-center gap-1.5"><span class="size-2 rounded-full bg-emerald-500"></span> Disetujui</span>
            <span class="flex items-center gap-1.5"><span class="size-2 rounded-full bg-amber-500"></span> Menunggu</span>
            <span class="flex items-center gap-1.5"><span class="size-2 rounded-full bg-slate-400"></span> Selesai</span>
            @unless ($isAdmin)<span class="flex items-center gap-1.5"><span class="size-3 rounded bg-indigo-50 ring-1 ring-indigo-200"></span> Peminjaman Anda</span>@endunless
        </div>
    </div>
</x-layouts.app>
