<x-layouts.app title="Product categories">
    <div class="space-y-6">
        <x-page-heading title="Product categories" description="Organize products into editable groups and control which groups can be used for new sales.">
            <x-slot:actions><x-button href="{{ route('admin.categories.create') }}">Add category</x-button></x-slot:actions>
        </x-page-heading>

        <form method="GET" action="{{ route('admin.categories.index') }}" class="card flex flex-col gap-3 p-4 sm:flex-row sm:items-end">
            <div class="min-w-0 flex-1"><x-search-field name="search" label="Search categories" placeholder="Search name or description" :value="$search" /></div>
            <div class="flex gap-2">
                <x-button type="submit" class="flex-1 sm:flex-none">Search</x-button>
                @if($search)<x-button href="{{ route('admin.categories.index') }}" variant="secondary" class="flex-1 sm:flex-none">Clear</x-button>@endif
            </div>
        </form>

        @if($categories->isEmpty())
            <x-empty-state title="{{ $search ? 'No categories match your search' : 'No product categories yet' }}" description="{{ $search ? 'Try a different category name or description.' : 'Add the first category to prepare for product registration.' }}">
                <x-slot:icon><svg viewBox="0 0 24 24" class="size-6" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 5.5A1.5 1.5 0 0 1 5.5 4h4A1.5 1.5 0 0 1 11 5.5v4A1.5 1.5 0 0 1 9.5 11h-4A1.5 1.5 0 0 1 4 9.5zM13 5.5A1.5 1.5 0 0 1 14.5 4h4A1.5 1.5 0 0 1 20 5.5v4a1.5 1.5 0 0 1-1.5 1.5h-4A1.5 1.5 0 0 1 13 9.5zM4 14.5A1.5 1.5 0 0 1 5.5 13h4a1.5 1.5 0 0 1 1.5 1.5v4A1.5 1.5 0 0 1 9.5 20h-4A1.5 1.5 0 0 1 4 18.5zM13 14.5a1.5 1.5 0 0 1 1.5-1.5h4a1.5 1.5 0 0 1 1.5 1.5v4a1.5 1.5 0 0 1-1.5 1.5h-4a1.5 1.5 0 0 1-1.5-1.5z"/></svg></x-slot:icon>
                @if(! $search)
                    <x-slot:action>
                        <x-button href="{{ route('admin.categories.create') }}">Add category</x-button>
                    </x-slot:action>
                @endif
            </x-empty-state>
        @else
            <x-responsive-table label="Product categories">
                <x-slot:mobile>
                    @foreach($categories as $category)
                        <article class="p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0"><h2 class="font-bold text-slate-950">{{ $category->name }}</h2><p class="mt-1 text-sm leading-5 text-slate-600">{{ $category->description ?: 'No description provided.' }}</p></div>
                                <x-status-badge :variant="$category->is_active ? 'success' : 'danger'">{{ $category->is_active ? 'Active' : 'Inactive' }}</x-status-badge>
                            </div>
                            <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
                                <div><dt class="text-slate-500">Products</dt><dd class="mt-0.5 font-medium text-slate-800">{{ $category->products_count }}</dd></div>
                                <div><dt class="text-slate-500">Created</dt><dd class="mt-0.5 font-medium text-slate-800">{{ $formatter->businessDate($category->created_at) }}</dd></div>
                                <div><dt class="text-slate-500">Last updated</dt><dd class="mt-0.5 font-medium text-slate-800">{{ $formatter->businessDate($category->updated_at) }}</dd></div>
                            </dl>
                            <div class="mt-4 flex flex-wrap gap-2"><x-button href="{{ route('admin.categories.edit', $category) }}" variant="secondary">Edit</x-button>@include('admin.categories._status-action', ['dialogPrefix' => 'mobile'])</div>
                        </article>
                    @endforeach
                </x-slot:mobile>

                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-5 py-3 font-semibold">Category</th><th class="px-5 py-3 font-semibold">Products</th><th class="px-5 py-3 font-semibold">Status</th><th class="px-5 py-3 font-semibold">Created</th><th class="px-5 py-3 font-semibold">Last updated</th><th class="px-5 py-3 text-right font-semibold">Actions</th></tr></thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    @foreach($categories as $category)
                        <tr>
                            <td class="max-w-md px-5 py-4"><p class="font-semibold text-slate-900">{{ $category->name }}</p><p class="mt-1 line-clamp-2 text-sm leading-5 text-slate-500">{{ $category->description ?: 'No description provided.' }}</p></td>
                            <td class="px-5 py-4 font-medium text-slate-700">{{ $category->products_count }}</td>
                            <td class="px-5 py-4"><x-status-badge :variant="$category->is_active ? 'success' : 'danger'">{{ $category->is_active ? 'Active' : 'Inactive' }}</x-status-badge></td>
                            <td class="whitespace-nowrap px-5 py-4 text-slate-600">{{ $formatter->businessDate($category->created_at) }}</td>
                            <td class="whitespace-nowrap px-5 py-4 text-slate-600">{{ $formatter->businessDate($category->updated_at) }}</td>
                            <td class="px-5 py-4"><div class="flex flex-wrap justify-end gap-2"><x-button href="{{ route('admin.categories.edit', $category) }}" variant="secondary">Edit</x-button>@include('admin.categories._status-action', ['dialogPrefix' => 'desktop'])</div></td>
                        </tr>
                    @endforeach
                </tbody>
            </x-responsive-table>
            <x-pagination :paginator="$categories" />
        @endif
    </div>
</x-layouts.app>
