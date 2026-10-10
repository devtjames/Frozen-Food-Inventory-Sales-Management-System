@php($editing = isset($product))

<div class="grid gap-6 lg:grid-cols-[15rem_minmax(0,1fr)]">
    <div>
        <label for="image" class="form-label">Product image</label>
        <div class="grid min-h-40 place-items-center overflow-hidden rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-4">
            <img x-cloak x-show="preview && !removeImage" :src="preview" alt="Selected product image preview" class="max-h-40 max-w-full object-contain">
            <div x-show="!preview || removeImage" class="text-center text-sm text-slate-500">
                <svg viewBox="0 0 24 24" class="mx-auto mb-2 size-10" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M4 19V5a2 2 0 0 1 2-2h8l6 6v10a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2Z"/><path d="M14 3v6h6M8 16l2.5-3 2 2 1.5-2 2 3"/></svg>
                No image selected
            </div>
        </div>
        <input id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp" class="mt-3 block min-h-11 w-full cursor-pointer rounded-xl border border-slate-300 bg-white text-sm text-slate-600 file:mr-3 file:min-h-11 file:border-0 file:border-r file:border-slate-300 file:bg-slate-50 file:px-3 file:font-semibold file:text-slate-700 hover:file:bg-slate-100" @change="previewFile($event)" @if($errors->has('image')) aria-invalid="true" aria-describedby="image-error" @else aria-describedby="image-help" @endif>
        <p id="image-help" class="mt-1.5 text-xs leading-5 text-slate-500">JPG, PNG, or WebP. Maximum {{ config('products.image_max_kilobytes') }} KB and 3000 × 3000 pixels.</p>
        @error('image')<p id="image-error" class="form-error">{{ $message }}</p>@enderror

        @if($editing && $product->image_path)
            <label class="mt-3 flex min-h-11 cursor-pointer items-center gap-3 rounded-xl border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700">
                <input type="hidden" name="remove_image" value="0">
                <input type="checkbox" name="remove_image" value="1" x-model="removeImage" class="size-5 rounded border-slate-300 text-brand-700 focus:ring-brand-600">
                Remove current image
            </label>
        @elseif($editing)
            <input type="hidden" name="remove_image" value="0">
        @endif
    </div>

    <div class="space-y-5">
        <x-input-field name="name" label="Product name" :value="$product->name ?? null" maxlength="150" autocomplete="off" required />
        <x-searchable-dropdown name="category_id" label="Category" :options="$categories" :selected="$product->category_id ?? null" placeholder="Select a category" search-placeholder="Search categories" required />
        <x-textarea-field name="description" label="Description" :value="$product->description ?? null" rows="4" maxlength="2000" help="Optional. Add details that help staff identify this product." />

        <div class="grid gap-5 sm:grid-cols-2">
            <x-input-field name="selling_price" label="Selling price per kg (₦)" type="number" :value="$product->selling_price ?? null" min="0.01" max="9999999999.99" step="0.01" inputmode="decimal" required help="Selling price only. Purchase costs belong to stock receipts." />
            <x-input-field name="low_stock_threshold" label="Low-stock threshold (kg)" type="number" :value="$product->low_stock_threshold ?? $defaultLowStockThreshold ?? null" min="0" max="999999999.999" step="0.001" inputmode="decimal" required help="The product is flagged as low stock when its batch-derived balance reaches this value." />
        </div>

        @if($editing)
            <div>
                <span class="form-label">Current status</span>
                <x-status-badge :variant="$product->is_active ? 'success' : 'danger'">{{ $product->is_active ? 'Active' : 'Inactive' }}</x-status-badge>
                <p class="mt-1.5 text-sm text-slate-500">Use the product list or details page to change status with confirmation.</p>
            </div>
        @else
            <x-dropdown name="is_active" label="Product status" :options="['1' => 'Active', '0' => 'Inactive']" selected="1" required />
        @endif

        <p class="rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm leading-6 text-sky-900">Available stock is not edited here. Quantities and purchase costs are recorded through stock receiving and auditable adjustments.</p>
    </div>
</div>
