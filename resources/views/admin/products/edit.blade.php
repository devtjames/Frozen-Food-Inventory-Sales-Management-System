<x-layouts.app title="Edit product">
    <div class="mx-auto max-w-5xl space-y-6">
        <x-page-heading title="Edit product" description="Update {{ $product->name }} and its selling price without changing inventory quantities." />
        <x-responsive-card>
            <form method="POST" action="{{ route('admin.products.update', $product) }}" enctype="multipart/form-data" class="space-y-6" x-data="{ submitting: false, preview: @js($imageUrl), removeImage: {{ old('remove_image') ? 'true' : 'false' }}, previewFile(event) { const file = event.target.files[0]; if (!file) return; this.removeImage = false; const reader = new FileReader(); reader.onload = (loadEvent) => this.preview = loadEvent.target.result; reader.readAsDataURL(file); } }" @submit="submitting = true">
                @csrf
                @method('PUT')
                @include('admin.products._form')
                <div class="flex flex-col-reverse gap-2 border-t border-slate-200 pt-5 sm:flex-row sm:justify-end">
                    <x-button href="{{ route('admin.products.show', $product) }}" variant="secondary">Cancel</x-button>
                    <x-button type="submit" x-bind:disabled="submitting"><span x-show="!submitting">Save changes</span><span x-cloak x-show="submitting"><x-loading-indicator label="Saving…" /></span></x-button>
                </div>
            </form>
        </x-responsive-card>
    </div>
</x-layouts.app>
