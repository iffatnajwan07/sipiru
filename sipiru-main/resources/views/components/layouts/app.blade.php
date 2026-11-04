@props(['title' => null])
@php
    $user = auth()->user();
    $isAdmin = $user?->isAdmin() ?? false;
    $pendingCount = $isAdmin ? \App\Models\Booking::status(\App\Enums\BookingStatus::Pending)->count() : null;
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title.' · ' : '' }}{{ config('app.name') }}</title>
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen" x-data="{ sidebar: false }">
    {{-- Overlay mobile --}}
    <div x-cloak x-show="sidebar" x-transition.opacity @click="sidebar = false" class="fixed inset-0 z-30 bg-slate-900/40 lg:hidden"></div>

    <aside :class="sidebar ? 'translate-x-0' : '-translate-x-full'"
           class="fixed inset-y-0 left-0 z-40 flex w-72 -translate-x-full flex-col border-r border-slate-200 bg-white transition-transform lg:translate-x-0">
        <div class="flex h-16 items-center justify-between px-5">
            <a href="{{ $isAdmin ? route('admin.dashboard') : ($user ? route('dashboard') : route('calendar')) }}"><x-logo /></a>
            <button class="btn btn-ghost btn-sm lg:hidden" @click="sidebar = false" aria-label="Tutup menu"><x-icon name="x" class="size-4" /></button>
        </div>
        <nav class="flex-1 space-y-0.5 overflow-y-auto px-3 pb-6">
            @if ($isAdmin)
                <x-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')" icon="layout-dashboard">Dashboard</x-nav-link>
                <div class="nav-section">Master Data</div>
                <x-nav-link :href="route('admin.buildings.index')" :active="request()->routeIs('admin.buildings.*')" icon="building-2">Gedung</x-nav-link>
                <x-nav-link :href="route('admin.rooms.index')" :active="request()->routeIs('admin.rooms.*')" icon="door-open">Ruangan</x-nav-link>
                <x-nav-link :href="route('admin.periods.index')" :active="request()->routeIs('admin.periods.*')" icon="calendar-range">Periode</x-nav-link>
                <div class="nav-section">Peminjaman</div>
                <x-nav-link :href="route('admin.bookings.index')" :active="request()->routeIs('admin.bookings.*') && request('status') !== 'pending'" icon="clipboard-list">Semua Peminjaman</x-nav-link>
                <x-nav-link :href="route('admin.bookings.index', ['status' => 'pending'])" :active="request()->routeIs('admin.bookings.index') && request('status') === 'pending'" icon="hourglass" :count="$pendingCount">Menunggu Persetujuan</x-nav-link>
                <x-nav-link :href="route('admin.calendar')" :active="request()->routeIs('admin.calendar')" icon="calendar-days">Kalender</x-nav-link>
                <div class="nav-section">Sistem</div>
                <x-nav-link :href="route('admin.users.index')" :active="request()->routeIs('admin.users.*')" icon="users">Users</x-nav-link>
                <x-nav-link :href="route('admin.reports.index')" :active="request()->routeIs('admin.reports.*')" icon="chart-column">Reports</x-nav-link>
                <x-nav-link :href="route('admin.settings.email')" :active="request()->routeIs('admin.settings.*')" icon="mail">Settings · Email</x-nav-link>
                <x-nav-link :href="route('admin.activity-logs.index')" :active="request()->routeIs('admin.activity-logs.*')" icon="history">Activity Log</x-nav-link>
                <x-nav-link :href="route('admin.login-logs.index')" :active="request()->routeIs('admin.login-logs.*')" icon="log-in">Login Activity</x-nav-link>
            @elseif ($user)
                <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" icon="layout-dashboard">Dashboard</x-nav-link>
                <x-nav-link :href="route('bookings.create')" :active="request()->routeIs('bookings.create')" icon="calendar-plus">Ajukan Peminjaman</x-nav-link>
                <x-nav-link :href="route('bookings.index')" :active="request()->routeIs('bookings.index', 'bookings.show')" icon="clipboard-list">Peminjaman Saya</x-nav-link>
                <x-nav-link :href="route('calendar')" :active="request()->routeIs('calendar')" icon="calendar-days">Kalender</x-nav-link>
                <x-nav-link :href="route('policy.show')" :active="request()->routeIs('policy.*')" icon="shield-check">Kebijakan</x-nav-link>
                <x-nav-link :href="route('profile.show')" :active="request()->routeIs('profile.*')" icon="user">Profile</x-nav-link>
            @else
                <div class="nav-section">Publik</div>
                <x-nav-link :href="route('calendar')" :active="request()->routeIs('calendar')" icon="calendar-days">Jadwal Kalender</x-nav-link>
                <x-nav-link :href="route('login')" :active="request()->routeIs('login')" icon="log-in">Masuk / Login</x-nav-link>
            @endif
        </nav>
        <div class="border-t border-slate-200 p-3">
            @if ($user)
                <div class="flex items-center gap-3 rounded-lg px-2 py-2">
                    <div class="grid size-9 shrink-0 place-items-center rounded-full bg-slate-100 text-sm font-semibold text-slate-600">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</div>
                    <div class="min-w-0 flex-1">
                        <div class="truncate text-sm font-medium text-slate-900">{{ $user->name }}</div>
                        <div class="truncate text-xs text-slate-500">{{ $user->user_type->label() }}</div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="btn btn-ghost btn-sm" title="Keluar" aria-label="Keluar"><x-icon name="log-out" class="size-4" /></button>
                    </form>
                </div>
            @else
                <div class="p-1">
                    <a href="{{ route('login') }}" class="btn btn-primary w-full justify-center text-sm">
                        <x-icon name="log-in" class="size-4 mr-1" /> Masuk ke Sistem
                    </a>
                </div>
            @endif
        </div>
    </aside>

    <div class="lg:pl-72">
        <header class="sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-slate-200 bg-white/90 px-4 backdrop-blur lg:hidden">
            <button class="btn btn-ghost btn-sm" @click="sidebar = true" aria-label="Buka menu"><x-icon name="menu" class="size-5" /></button>
            <x-logo />
        </header>
        <main class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
            <x-flash />
            {{ $slot }}
        </main>
    </div>
    @stack('scripts')
</body>
</html>