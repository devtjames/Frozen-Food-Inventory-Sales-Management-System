<x-layouts.app title="Add cashier">
    <div class="mx-auto max-w-3xl space-y-6">
        <x-page-heading title="Add cashier" description="Create a cashier account with limited sales access. Administrator privileges cannot be assigned here." />
        <x-responsive-card>
            <form method="POST" action="{{ route('admin.staff.store') }}" class="space-y-6" x-data="{ submitting: false }" @submit="submitting = true">
                @csrf
                @include('admin.staff._form')
                <div class="flex flex-col-reverse gap-2 border-t border-slate-200 pt-5 sm:flex-row sm:justify-end">
                    <x-button href="{{ route('admin.staff.index') }}" variant="secondary">Cancel</x-button>
                    <x-button type="submit" x-bind:disabled="submitting"><span x-show="!submitting">Create cashier</span><span x-cloak x-show="submitting">Creating…</span></x-button>
                </div>
            </form>
        </x-responsive-card>
    </div>
</x-layouts.app>
