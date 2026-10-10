<nav {{ $attributes->class(['flex gap-2 overflow-x-auto pb-1']) }} aria-label="Inventory sections">
    @foreach([
        'admin.inventory.index' => 'Available stock',
        'admin.inventory.receipts.index' => 'Receiving history',
        'admin.inventory.movements.index' => 'Movements',
        'admin.inventory.adjustments.index' => 'Adjustments',
        'admin.inventory.losses.index' => 'Stock losses',
    ] as $routeName => $label)
        <a href="{{ route($routeName) }}" class="inline-flex min-h-11 shrink-0 items-center rounded-xl border px-4 py-2 text-sm font-semibold transition {{ request()->routeIs($routeName) || ($routeName === 'admin.inventory.receipts.index' && request()->routeIs('admin.inventory.receipts.*')) || ($routeName === 'admin.inventory.adjustments.index' && request()->routeIs('admin.inventory.adjustments.*')) || ($routeName === 'admin.inventory.losses.index' && request()->routeIs('admin.inventory.losses.*')) ? 'border-brand-700 bg-brand-700 text-white' : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50' }}">{{ $label }}</a>
    @endforeach
</nav>
