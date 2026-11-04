@props(['title' => null, 'wide' => false])
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
<body class="min-h-screen">
    <div class="flex min-h-screen flex-col items-center justify-center px-4 py-10">
        <a href="{{ url('/') }}" class="mb-8"><x-logo /></a>
        <div class="w-full {{ $wide ? 'max-w-2xl' : 'max-w-md' }}">
            <x-flash :inline="true" />
            {{ $slot }}
        </div>
        <p class="mt-8 text-center text-xs text-slate-400">&copy; {{ date('Y') }} Universitas Syiah Kuala · {{ config('app.name') }}</p>
    </div>
</body>
</html>
