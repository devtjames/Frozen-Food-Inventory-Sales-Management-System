<x-layouts.app title="Edit cashier">
    <div class="mx-auto max-w-3xl space-y-6">
        <x-page-heading title="Edit cashier" description="Update {{ $staff->name }}'s contact and sign-in information." />
        <x-responsive-card>
            <form method="POST" action="{{ route('admin.staff.update', $staff) }}" class="space-y-6" x-data="{ submitting: false }" @submit="submitting = true">
                @csrf
                @method('PUT')
                @include('admin.staff._form', ['staff' => $staff])
                <div class="flex flex-col-reverse gap-2 border-t border-slate-200 pt-5 sm:flex-row sm:justify-end">
                    <x-button href="{{ route('admin.staff.show', $staff) }}" variant="secondary">Cancel</x-button>
                    <x-button type="submit" x-bind:disabled="submitting"><span x-show="!submitting">Save changes</span><span x-cloak x-show="submitting">Saving…</span></x-button>
                </div>
            </form>
        </x-responsive-card>
    </div>
</x-layouts.app>
