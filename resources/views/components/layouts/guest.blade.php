@props(['title' => null])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title.' · ' : '' }}{{ $businessSettings->business_name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body>
    <main class="relative flex min-h-screen items-center justify-center overflow-hidden bg-slate-50 px-4 py-8 sm:px-6">
        <div class="pointer-events-none absolute inset-x-0 top-0 h-64 bg-gradient-to-b from-brand-100/80 to-transparent"></div>
        <div class="relative w-full max-w-md">
            <a href="{{ route(request()->routeIs('setup.*') ? 'setup.create' : 'login') }}" class="mb-7 flex justify-center" aria-label="{{ $businessSettings->business_name }} access"><x-app-logo :settings="$businessSettings" :logo-url="$businessLogoUrl" /></a>
            {{ $slot }}
            <p class="mt-6 text-center text-xs leading-5 text-slate-500">Secure access for authorised store staff only.</p>
        </div>
    </main>
    @livewireScripts
</body>
</html>
