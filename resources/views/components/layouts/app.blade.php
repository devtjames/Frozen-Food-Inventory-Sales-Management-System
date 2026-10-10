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
<body x-data="{ mobileNav: false, sidebarCollapsed: false, profileOpen: false }" x-init="sidebarCollapsed = localStorage.getItem('sidebar-collapsed') === 'true'" @keydown.escape.window="mobileNav = false; profileOpen = false" class="overflow-x-hidden bg-slate-50">
    <div class="min-h-screen lg:flex">
        <div x-cloak x-show="mobileNav" x-transition.opacity class="fixed inset-0 z-40 bg-slate-950/50 lg:hidden" @click="mobileNav = false" aria-hidden="true"></div>

        <aside :class="mobileNav ? 'translate-x-0' : '-translate-x-full'" class="fixed inset-y-0 left-0 z-50 flex w-72 max-w-[88vw] flex-col border-r border-slate-200 bg-white transition-transform duration-200 lg:sticky lg:top-0 lg:h-screen lg:translate-x-0" :style="sidebarCollapsed ? 'width: 5.25rem' : ''" aria-label="Primary navigation">
            <div class="flex h-18 items-center justify-between border-b border-slate-200 px-4">
                <x-app-logo :settings="$businessSettings" :logo-url="$businessLogoUrl" x-show="!sidebarCollapsed" />
                <x-app-logo :settings="$businessSettings" :logo-url="$businessLogoUrl" compact x-show="sidebarCollapsed" x-cloak />
                <button type="button" @click="mobileNav = false" class="grid size-11 place-items-center rounded-xl text-slate-500 hover:bg-slate-100 lg:hidden" aria-label="Close navigation">
                    <svg viewBox="0 0 24 24" class="size-6" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 6 12 12M18 6 6 18" stroke-linecap="round"/></svg>
                </button>
            </div>

            <nav class="flex-1 space-y-1 overflow-y-auto p-3">
                @php($dashboardRoute = auth()->user()->dashboardRoute())
                <a href="{{ route($dashboardRoute) }}" class="flex min-h-11 items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('*.dashboard') ? 'bg-brand-50 text-brand-800' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-950' }}" @click="mobileNav = false">
                    <svg viewBox="0 0 24 24" class="size-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 10.5 12 4l8 6.5V20H4z" stroke-linejoin="round"/><path d="M9 20v-6h6v6"/></svg>
                    <span x-show="!sidebarCollapsed">Dashboard</span>
                </a>
                @if(auth()->user()->isAdministrator())
                    <a href="{{ route('admin.pos') }}" class="flex min-h-11 items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('admin.pos') ? 'bg-brand-50 text-brand-800' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-950' }}" @click="mobileNav = false">
                        <svg viewBox="0 0 24 24" class="size-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 5h16v14H4zM4 9h16M8 14h3" stroke-linejoin="round"/><path d="M15 14h1M18 14h1" stroke-linecap="round"/></svg>
                        <span x-show="!sidebarCollapsed">Point of sale</span>
                    </a>
                    <a href="{{ route('admin.staff.index') }}" class="flex min-h-11 items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('admin.staff.*') ? 'bg-brand-50 text-brand-800' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-950' }}" @click="mobileNav = false">
                        <svg viewBox="0 0 24 24" class="size-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M16 20v-1.5a4.5 4.5 0 0 0-4.5-4.5h-4A4.5 4.5 0 0 0 3 18.5V20M9.5 10a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM16 8h5M18.5 5.5v5" stroke-linecap="round"/></svg>
                        <span x-show="!sidebarCollapsed">Staff management</span>
                    </a>
                    <a href="{{ route('admin.business-settings.edit') }}" class="flex min-h-11 items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('admin.business-settings.*') ? 'bg-brand-50 text-brand-800' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-950' }}" @click="mobileNav = false">
                        <svg viewBox="0 0 24 24" class="size-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 10h16v10H4zM6 10V7l6-3 6 3v3M8 14h2v3H8zM14 14h2v3h-2z" stroke-linejoin="round"/><path d="M3 20h18" stroke-linecap="round"/></svg>
                        <span x-show="!sidebarCollapsed">Business settings</span>
                    </a>
                    <a href="{{ route('admin.categories.index') }}" class="flex min-h-11 items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('admin.categories.*') ? 'bg-brand-50 text-brand-800' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-950' }}" @click="mobileNav = false">
                        <svg viewBox="0 0 24 24" class="size-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 5.5A1.5 1.5 0 0 1 5.5 4h4A1.5 1.5 0 0 1 11 5.5v4A1.5 1.5 0 0 1 9.5 11h-4A1.5 1.5 0 0 1 4 9.5zM13 5.5A1.5 1.5 0 0 1 14.5 4h4A1.5 1.5 0 0 1 20 5.5v4a1.5 1.5 0 0 1-1.5 1.5h-4A1.5 1.5 0 0 1 13 9.5zM4 14.5A1.5 1.5 0 0 1 5.5 13h4a1.5 1.5 0 0 1 1.5 1.5v4A1.5 1.5 0 0 1 9.5 20h-4A1.5 1.5 0 0 1 4 18.5zM13 14.5a1.5 1.5 0 0 1 1.5-1.5h4a1.5 1.5 0 0 1 1.5 1.5v4a1.5 1.5 0 0 1-1.5 1.5h-4a1.5 1.5 0 0 1-1.5-1.5z"/></svg>
                        <span x-show="!sidebarCollapsed">Product categories</span>
                    </a>
                    <a href="{{ route('admin.products.index') }}" class="flex min-h-11 items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('admin.products.*') ? 'bg-brand-50 text-brand-800' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-950' }}" @click="mobileNav = false">
                        <svg viewBox="0 0 24 24" class="size-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 8.5 12 4l8 4.5v8L12 21l-8-4.5z" stroke-linejoin="round"/><path d="m4 8.5 8 4.5 8-4.5M12 13v8" stroke-linejoin="round"/></svg>
                        <span x-show="!sidebarCollapsed">Products</span>
                    </a>
                    <a href="{{ route('admin.inventory.index') }}" class="flex min-h-11 items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('admin.inventory.*') && ! request()->routeIs('admin.inventory.losses.*') ? 'bg-brand-50 text-brand-800' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-950' }}" @click="mobileNav = false">
                        <svg viewBox="0 0 24 24" class="size-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 7h16v13H4zM7 4h10l3 3H4z" stroke-linejoin="round"/><path d="M8 12h8M8 16h5" stroke-linecap="round"/></svg>
                        <span x-show="!sidebarCollapsed">Inventory</span>
                    </a>
                    <a href="{{ route('admin.inventory.losses.index') }}" class="flex min-h-11 items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('admin.inventory.losses.*') ? 'bg-brand-50 text-brand-800' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-950' }}" @click="mobileNav = false">
                        <svg viewBox="0 0 24 24" class="size-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 3 3.5 19h17z" stroke-linejoin="round"/><path d="M12 9v4M12 17h.01" stroke-linecap="round"/></svg>
                        <span x-show="!sidebarCollapsed">Stock losses</span>
                    </a>
                    <a href="{{ route('admin.sales.index') }}" class="flex min-h-11 items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('admin.sales.*') ? 'bg-brand-50 text-brand-800' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-950' }}" @click="mobileNav = false">
                        <svg viewBox="0 0 24 24" class="size-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M5 3h14v18l-3-2-4 2-4-2-3 2z" stroke-linejoin="round"/><path d="M8 8h8M8 12h8M8 16h5" stroke-linecap="round"/></svg>
                        <span x-show="!sidebarCollapsed">Sales history</span>
                    </a>
                    <a href="{{ route('admin.expenses.index') }}" class="flex min-h-11 items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('admin.expenses.*') || request()->routeIs('admin.expense-categories.*') ? 'bg-brand-50 text-brand-800' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-950' }}" @click="mobileNav = false">
                        <svg viewBox="0 0 24 24" class="size-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 6h16v13H4z" stroke-linejoin="round"/><path d="M4 10h16M8 15h4M16 14v2" stroke-linecap="round"/></svg>
                        <span x-show="!sidebarCollapsed">Expenses</span>
                    </a>
                    <a href="{{ route('admin.notifications.index') }}" class="flex min-h-11 items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('admin.notifications.*') ? 'bg-brand-50 text-brand-800' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-950' }}" @click="mobileNav = false">
                        <svg viewBox="0 0 24 24" class="size-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M18 9a6 6 0 0 0-12 0c0 7-3 7-3 8h18c0-1-3-1-3-8Z" stroke-linejoin="round"/><path d="M10 21h4" stroke-linecap="round"/></svg>
                        <span x-show="!sidebarCollapsed" class="flex min-w-0 flex-1 items-center justify-between gap-2"><span>Notifications</span>@if($unreadNotificationCount > 0)<span class="rounded-full bg-red-600 px-2 py-0.5 text-xs text-white">{{ $unreadNotificationCount > 99 ? '99+' : $unreadNotificationCount }}</span>@endif</span>
                    </a>
                    <a href="{{ route('admin.reports.show', 'sales') }}" class="flex min-h-11 items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('admin.reports.*') ? 'bg-brand-50 text-brand-800' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-950' }}" @click="mobileNav = false">
                        <svg viewBox="0 0 24 24" class="size-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 20V10m5 10V4m6 16v-7m5 7V7" stroke-linecap="round"/><path d="M3 20h18"/></svg>
                        <span x-show="!sidebarCollapsed">Reports</span>
                    </a>
                @endif
                @if(auth()->user()->isCashier())
                    <a href="{{ route('cashier.pos') }}" class="flex min-h-11 items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('cashier.pos') ? 'bg-brand-50 text-brand-800' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-950' }}" @click="mobileNav = false">
                        <svg viewBox="0 0 24 24" class="size-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 5h16v14H4zM4 9h16M8 14h3" stroke-linejoin="round"/><path d="M15 14h1M18 14h1" stroke-linecap="round"/></svg>
                        <span x-show="!sidebarCollapsed">Point of sale</span>
                    </a>
                    <a href="{{ route('cashier.sales.index') }}" class="flex min-h-11 items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('cashier.sales.*') ? 'bg-brand-50 text-brand-800' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-950' }}" @click="mobileNav = false">
                        <svg viewBox="0 0 24 24" class="size-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M5 3h14v18l-3-2-4 2-4-2-3 2z" stroke-linejoin="round"/><path d="M8 8h8M8 12h8M8 16h5" stroke-linecap="round"/></svg>
                        <span x-show="!sidebarCollapsed">Sales history</span>
                    </a>
                @endif
                <a href="{{ route('password.edit') }}" class="flex min-h-11 items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('password.*') ? 'bg-brand-50 text-brand-800' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-950' }}" @click="mobileNav = false">
                    <svg viewBox="0 0 24 24" class="size-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="5" y="10" width="14" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
                    <span x-show="!sidebarCollapsed">Change password</span>
                </a>
            </nav>

            <div class="hidden border-t border-slate-200 p-3 lg:block">
                <button type="button" @click="sidebarCollapsed = !sidebarCollapsed; localStorage.setItem('sidebar-collapsed', sidebarCollapsed)" class="flex min-h-11 w-full items-center gap-3 rounded-xl px-3 text-sm font-semibold text-slate-600 hover:bg-slate-100" :aria-label="sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'">
                    <svg viewBox="0 0 24 24" class="size-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m14 6-6 6 6 6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <span x-show="!sidebarCollapsed">Collapse sidebar</span>
                </button>
            </div>
        </aside>

        <div class="min-w-0 flex-1">
            <header class="sticky top-0 z-30 flex h-18 items-center justify-between border-b border-slate-200 bg-white/95 px-3 backdrop-blur sm:px-6">
                <div class="flex min-w-0 items-center gap-2">
                    <button type="button" @click="mobileNav = true" class="grid size-11 shrink-0 place-items-center rounded-xl text-slate-600 hover:bg-slate-100 lg:hidden" aria-label="Open navigation" aria-controls="mobile-navigation" :aria-expanded="mobileNav">
                        <svg viewBox="0 0 24 24" class="size-6" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16M4 12h16M4 17h16" stroke-linecap="round"/></svg>
                    </button>
                    <div class="truncate text-sm font-semibold text-slate-700 sm:text-base">{{ $title ?? 'Dashboard' }}</div>
                </div>

                <div class="flex items-center gap-1 sm:gap-2">
                    @if(auth()->user()->isAdministrator())
                        <a href="{{ route('admin.notifications.index') }}" class="relative grid size-11 place-items-center rounded-xl text-slate-600 hover:bg-slate-100" aria-label="Notifications{{ $unreadNotificationCount ? ', '.$unreadNotificationCount.' unread' : '' }}">
                            <svg viewBox="0 0 24 24" class="size-6" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M18 9a6 6 0 0 0-12 0c0 7-3 7-3 8h18c0-1-3-1-3-8Z" stroke-linejoin="round"/><path d="M10 21h4" stroke-linecap="round"/></svg>
                            @if($unreadNotificationCount > 0)<span class="absolute right-1 top-1 grid min-h-5 min-w-5 place-items-center rounded-full bg-red-600 px-1 text-[10px] font-bold text-white">{{ $unreadNotificationCount > 99 ? '99+' : $unreadNotificationCount }}</span>@endif
                        </a>
                    @endif
                    <div class="relative" @click.outside="profileOpen = false">
                        <button type="button" @click="profileOpen = !profileOpen" class="flex min-h-11 items-center gap-2 rounded-xl px-2 py-1.5 hover:bg-slate-100" :aria-expanded="profileOpen" aria-haspopup="menu">
                            <span class="grid size-8 place-items-center rounded-full bg-brand-100 text-sm font-bold text-brand-800">{{ strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                            <span class="hidden max-w-40 truncate text-left sm:block"><span class="block truncate text-sm font-semibold text-slate-900">{{ auth()->user()->name }}</span><span class="block text-xs text-slate-500">{{ auth()->user()->role->label() }}</span></span>
                            <svg viewBox="0 0 20 20" class="hidden size-4 text-slate-500 sm:block" fill="currentColor"><path fill-rule="evenodd" d="M5.2 7.2a.75.75 0 0 1 1.1 0L10 10.9l3.7-3.7a.75.75 0 1 1 1.1 1.1l-4.2 4.2a.75.75 0 0 1-1.1 0L5.2 8.3a.75.75 0 0 1 0-1.1Z" clip-rule="evenodd"/></svg>
                        </button>
                        <div x-cloak x-show="profileOpen" x-transition class="absolute right-0 mt-2 w-56 rounded-xl border border-slate-200 bg-white p-2 shadow-lg" role="menu">
                            <div class="border-b border-slate-100 px-3 py-2 sm:hidden"><p class="truncate text-sm font-semibold">{{ auth()->user()->name }}</p><p class="text-xs text-slate-500">{{ auth()->user()->role->label() }}</p></div>
                            <a href="{{ route('password.edit') }}" class="flex min-h-11 items-center rounded-lg px-3 text-sm font-medium text-slate-700 hover:bg-slate-100" role="menuitem">Change password</a>
                            <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="flex min-h-11 w-full items-center rounded-lg px-3 text-left text-sm font-medium text-red-700 hover:bg-red-50" role="menuitem">Sign out</button></form>
                        </div>
                    </div>
                </div>
            </header>

            <main class="mx-auto w-full max-w-7xl p-4 sm:p-6 lg:p-8">
                @if(session('status') === 'password-updated')<x-notification class="mb-5" message="Your password has been updated securely." />@endif
                @if(session('success'))<x-notification class="mb-5" :message="session('success')" />@endif
                {{ $slot }}
            </main>
        </div>
    </div>
    @livewireScripts
    @stack('scripts')
</body>
</html>
