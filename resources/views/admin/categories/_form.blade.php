@php($editing = isset($category))

<div class="space-y-5">
    <x-input-field name="name" label="Category name" :value="$category->name ?? null" maxlength="100" autocomplete="off" required />
    <x-textarea-field name="description" label="Description" :value="$category->description ?? null" rows="5" maxlength="1000" help="Optional. Briefly describe the products that belong in this category." />
    @if($editing)
        <div>
            <span class="form-label">Current status</span>
            <x-status-badge :variant="$category->is_active ? 'success' : 'danger'">{{ $category->is_active ? 'Active' : 'Inactive' }}</x-status-badge>
            <p class="mt-1.5 text-sm text-slate-500">Use the category list to change status with the appropriate confirmation.</p>
        </div>
    @else
        <x-dropdown name="is_active" label="Status" :options="['1' => 'Active', '0' => 'Inactive']" selected="1" required />
    @endif
    <p class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm leading-6 text-amber-900">Inactive categories and their products are preserved, but those products will not be available for new POS transactions until the category is reactivated.</p>
</div>
