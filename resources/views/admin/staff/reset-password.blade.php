<x-layouts.app title="Reset cashier password">
    <div class="mx-auto max-w-2xl space-y-6">
        <x-page-heading title="Reset password" description="Set a new password for {{ $staff->name }}. Existing sessions will be signed out." />
        <x-responsive-card>
            <form method="POST" action="{{ route('admin.staff.password.update', $staff) }}" class="space-y-5" x-data="{ submitting: false }" @submit="submitting = true">
                @csrf
                @method('PUT')
                <x-input-field name="password" label="New password" type="password" autocomplete="new-password" help="Use 12+ characters with uppercase, lowercase, number, and symbol." required />
                <x-input-field name="password_confirmation" label="Confirm new password" type="password" autocomplete="new-password" required />
                <div class="flex flex-col-reverse gap-2 border-t border-slate-200 pt-5 sm:flex-row sm:justify-end">
                    <x-button href="{{ route('admin.staff.show', $staff) }}" variant="secondary">Cancel</x-button>
                    <x-button type="submit" x-bind:disabled="submitting"><span x-show="!submitting">Reset password</span><span x-cloak x-show="submitting">Resetting…</span></x-button>
                </div>
            </form>
        </x-responsive-card>
    </div>
</x-layouts.app>
