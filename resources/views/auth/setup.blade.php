<x-layouts.guest title="Owner setup">
    <section class="card p-5 sm:p-8">
        <div class="mb-6">
            <x-status-badge variant="info">One-time setup</x-status-badge>
            <h1 class="mt-3 text-2xl font-bold tracking-tight text-slate-950">Create the owner account</h1>
            <p class="mt-1.5 text-sm leading-6 text-slate-600">Enter credentials known only to the store owner. This setup closes permanently after the administrator is created.</p>
        </div>

        <form method="POST" action="{{ route('setup.store') }}" class="space-y-5" x-data="{ submitting: false }" @submit="submitting = true">
            @csrf
            <x-input-field name="name" label="Owner's full name" autocomplete="name" required autofocus />
            <x-input-field name="username" label="Username (optional)" autocomplete="username" autocapitalize="none" spellcheck="false" help="Use letters, numbers, dashes, or underscores." />
            <x-input-field name="email" label="Email address" type="email" autocomplete="email" required />
            <x-input-field name="phone" label="Phone number (optional)" type="tel" autocomplete="tel" />
            <x-input-field name="password" label="Password" type="password" autocomplete="new-password" help="At least 12 characters with uppercase, lowercase, a number, and a symbol." required />
            <x-input-field name="password_confirmation" label="Confirm password" type="password" autocomplete="new-password" required />

            <x-button type="submit" class="w-full" x-bind:disabled="submitting">
                <span x-show="!submitting">Create owner account</span>
                <span x-cloak x-show="submitting" class="inline-flex items-center gap-2"><svg class="size-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle class="opacity-30" cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3"/><path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>Creating account&hellip;</span>
            </x-button>
        </form>
    </section>
</x-layouts.guest>
