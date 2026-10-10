<x-layouts.app title="Change password">
    <div class="mx-auto max-w-2xl space-y-6">
        <x-page-heading title="Change password" description="Use a strong, unique password to protect your account." />
        <x-responsive-card>
            <form method="POST" action="{{ route('password.update') }}" class="space-y-5" x-data="{ submitting: false }" @submit="submitting = true">
                @csrf
                @method('PUT')
                <x-input-field name="current_password" label="Current password" type="password" autocomplete="current-password" required />
                <x-input-field name="password" label="New password" type="password" autocomplete="new-password" help="Use 12+ characters with uppercase, lowercase, number, and symbol." required />
                <x-input-field name="password_confirmation" label="Confirm new password" type="password" autocomplete="new-password" required />
                <div class="flex flex-col-reverse gap-2 pt-1 sm:flex-row sm:justify-end">
                    <x-button href="{{ route(auth()->user()->dashboardRoute()) }}" variant="secondary">Cancel</x-button>
                    <x-button type="submit" x-bind:disabled="submitting"><span x-show="!submitting">Update password</span><span x-cloak x-show="submitting">Updating…</span></x-button>
                </div>
            </form>
        </x-responsive-card>
    </div>
</x-layouts.app>
