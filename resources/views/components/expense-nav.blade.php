<nav aria-label="Expense management" class="flex gap-2 overflow-x-auto pb-1">
    <a href="{{ route('admin.expenses.index') }}" class="inline-flex min-h-11 shrink-0 items-center rounded-xl px-4 text-sm font-semibold {{ request()->routeIs('admin.expenses.*') ? 'bg-brand-700 text-white' : 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50' }}">Expense history</a>
    <a href="{{ route('admin.expense-categories.index') }}" class="inline-flex min-h-11 shrink-0 items-center rounded-xl px-4 text-sm font-semibold {{ request()->routeIs('admin.expense-categories.*') ? 'bg-brand-700 text-white' : 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50' }}">Expense categories</a>
</nav>
