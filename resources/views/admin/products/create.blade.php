<x-layouts.app title="Add product">
    <div class="mx-auto max-w-5xl space-y-6">
        <x-page-heading title="Add product" description="Register a frozen food product and set its selling price per kilogram." />
        <x-responsive-card>
            <form method="POST" action="{{ route('admin.products.store') }}" enctype="multipart/form-data" class="space-y-6" x-data="{ submitting: false, preview: null, removeImage: false, previewFile(event) { const file = event.target.files[0]; if (!file) return; this.removeImage = false; const reader = new FileReader(); reader.onload = (loadEvent) => this.preview = loadEvent.target.result; reader.readAsDataURL(file); } }" @submit="submitting = true">
                @csrf
                @include('admin.products._form')
                <div class="flex flex-col-reverse gap-2 border-t border-slate-200 pt-5 sm:flex-row sm:justify-end">
                    <x-button href="{{ route('admin.products.index') }}" variant="secondary">Cancel</x-button>
                    <x-button type="submit" x-bind:disabled="submitting"><span x-show="!submitting">Create product</span><span x-cloak x-show="submitting"><x-loading-indicator label="Creating…" /></span></x-button>
                </div>
            </form>
        </x-responsive-card>
    </div>
</x-layouts.app>
