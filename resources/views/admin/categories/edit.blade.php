<x-layouts.app title="Edit category">
    <div class="mx-auto max-w-3xl space-y-6">
        <x-page-heading title="Edit category" description="Update {{ $category->name }} without affecting its future product records." />
        <x-responsive-card>
            <form method="POST" action="{{ route('admin.categories.update', $category) }}" class="space-y-6" x-data="{ submitting: false }" @submit="submitting = true">
                @csrf
                @method('PUT')
                @include('admin.categories._form')
                <div class="flex flex-col-reverse gap-2 border-t border-slate-200 pt-5 sm:flex-row sm:justify-end">
                    <x-button href="{{ route('admin.categories.index') }}" variant="secondary">Cancel</x-button>
                    <x-button type="submit" x-bind:disabled="submitting"><span x-show="!submitting">Save changes</span><span x-cloak x-show="submitting"><x-loading-indicator label="Saving…" /></span></x-button>
                </div>
            </form>
        </x-responsive-card>
    </div>
</x-layouts.app>
