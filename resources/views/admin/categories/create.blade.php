<x-layouts.app title="Add category">
    <div class="mx-auto max-w-3xl space-y-6">
        <x-page-heading title="Add category" description="Create a group that will organize related frozen food products." />
        <x-responsive-card>
            <form method="POST" action="{{ route('admin.categories.store') }}" class="space-y-6" x-data="{ submitting: false }" @submit="submitting = true">
                @csrf
                @include('admin.categories._form')
                <div class="flex flex-col-reverse gap-2 border-t border-slate-200 pt-5 sm:flex-row sm:justify-end">
                    <x-button href="{{ route('admin.categories.index') }}" variant="secondary">Cancel</x-button>
                    <x-button type="submit" x-bind:disabled="submitting"><span x-show="!submitting">Create category</span><span x-cloak x-show="submitting"><x-loading-indicator label="Creating…" /></span></x-button>
                </div>
            </form>
        </x-responsive-card>
    </div>
</x-layouts.app>
