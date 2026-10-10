<x-layouts.app title="Expenses">
    <div class="space-y-6">
        <x-page-heading title="Expense history" description="Monitor operating costs separately from inventory purchases and stock losses."><x-slot:actions><x-button href="{{ route('admin.expenses.create') }}">Record expense</x-button></x-slot:actions></x-page-heading>
        <x-expense-nav />

        <form method="GET" action="{{ route('admin.expenses.index') }}" class="card grid gap-4 p-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-search-field name="search" label="Search" placeholder="Description or note" :value="$filters['search'] ?? null" />
            <x-searchable-dropdown name="category" label="Category" :options="$categories" :selected="$filters['category'] ?? null" placeholder="All categories" search-placeholder="Search categories" />
            <x-input-field name="date_from" label="From date" type="date" :value="$filters['date_from'] ?? null" />
            <x-input-field name="date_to" label="To date" type="date" :value="$filters['date_to'] ?? null" />
            <div class="flex items-end gap-2 sm:col-span-2 xl:col-span-4"><x-button type="submit">Filter</x-button>@if(array_filter($filters))<x-button href="{{ route('admin.expenses.index') }}" variant="secondary">Clear</x-button>@endif</div>
        </form>

        <section class="rounded-2xl border border-brand-100 bg-brand-50 p-5"><p class="text-sm font-semibold text-brand-800">Total for selected filters</p><p class="mt-1 text-3xl font-bold text-brand-900">{{ $formatter->money($total) }}</p><p class="mt-1 text-sm text-brand-800">{{ $expenses->total() }} expense {{ Str::plural('record', $expenses->total()) }}</p></section>

        @if($expenses->isEmpty())
            <x-empty-state title="No expenses found" description="Record an expense or change the current filters." />
        @else
            <x-responsive-table label="Expense history">
                <x-slot:mobile>@foreach($expenses as $expense)<article class="p-4"><div class="flex items-start justify-between gap-3"><div><h2 class="font-bold text-slate-950">{{ $expense->description }}</h2><p class="text-sm text-slate-500">{{ $expense->category->name }}</p></div><p class="shrink-0 text-lg font-bold text-brand-900">{{ $formatter->money($expense->amount) }}</p></div><dl class="mt-4 grid grid-cols-2 gap-3 text-sm"><div><dt class="text-slate-500">Expense date</dt><dd class="font-semibold">{{ $formatter->businessDate($expense->expense_date) }}</dd></div><div><dt class="text-slate-500">Recorded by</dt><dd class="font-semibold">{{ $expense->recorder->name }}</dd></div></dl>@if($expense->note)<p class="mt-3 line-clamp-2 text-sm text-slate-600">{{ $expense->note }}</p>@endif<div class="mt-4 flex gap-2"><x-button href="{{ route('admin.expenses.show', $expense) }}" variant="secondary">View</x-button><x-button href="{{ route('admin.expenses.edit', $expense) }}" variant="secondary">Correct</x-button></div></article>@endforeach</x-slot:mobile>
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-5 py-3">Date</th><th class="px-5 py-3">Category</th><th class="px-5 py-3">Description</th><th class="px-5 py-3">Amount</th><th class="px-5 py-3">Recorded by</th><th class="px-5 py-3 text-right">Actions</th></tr></thead>
                <tbody class="divide-y divide-slate-200 bg-white">@foreach($expenses as $expense)<tr><td class="whitespace-nowrap px-5 py-4">{{ $formatter->businessDate($expense->expense_date) }}</td><td class="px-5 py-4 font-semibold">{{ $expense->category->name }}</td><td class="max-w-sm px-5 py-4"><p class="font-semibold">{{ $expense->description }}</p>@if($expense->note)<p class="mt-1 truncate text-sm text-slate-500">{{ $expense->note }}</p>@endif</td><td class="whitespace-nowrap px-5 py-4 font-bold text-brand-900">{{ $formatter->money($expense->amount) }}</td><td class="px-5 py-4">{{ $expense->recorder->name }}</td><td class="px-5 py-4"><div class="flex justify-end gap-2"><x-button href="{{ route('admin.expenses.show', $expense) }}" variant="secondary">View</x-button><x-button href="{{ route('admin.expenses.edit', $expense) }}" variant="secondary">Correct</x-button></div></td></tr>@endforeach</tbody>
            </x-responsive-table>
            <x-pagination :paginator="$expenses" />
        @endif
    </div>
</x-layouts.app>
