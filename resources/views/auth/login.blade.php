<x-layouts.guest title="Sign in">
    <section class="card p-5 sm:p-8">
        @if(session('status') === 'setup-complete')<x-notification class="mb-5" message="Owner setup is already complete. Sign in with an authorised account." />@endif
        <div class="mb-6"><h1 class="text-2xl font-bold tracking-tight text-slate-950">Welcome back</h1><p class="mt-1.5 text-sm leading-6 text-slate-600">Sign in with your authorised staff account.</p></div>

        <form method="POST" action="{{ route('login') }}" class="space-y-5" x-data="{ submitting: false, showPassword: false }" @submit="submitting = true">
            @csrf
            <x-input-field name="email" label="Email or username" type="text" autocomplete="username" autocapitalize="none" spellcheck="false" required autofocus />
            <div>
                <label for="password" class="form-label">Password<span class="text-red-600" aria-hidden="true"> *</span></label>
                <div class="relative">
                    <input id="password" name="password" x-bind:type="showPassword ? 'text' : 'password'" autocomplete="current-password" required class="form-control pr-20" @if($errors->has('password')) aria-invalid="true" aria-describedby="password-error" @endif>
                    <button type="button" @click="showPassword = !showPassword" class="absolute inset-y-0 right-0 min-w-16 rounded-r-xl px-3 text-sm font-semibold text-brand-800 hover:bg-brand-50" x-text="showPassword ? 'Hide' : 'Show'" :aria-label="showPassword ? 'Hide password' : 'Show password'"></button>
                </div>
                @error('password')<p id="password-error" class="form-error">{{ $message }}</p>@enderror
            </div>

            <label class="flex min-h-11 cursor-pointer items-center gap-3 text-sm text-slate-700">
                <input type="checkbox" name="remember" value="1" class="size-5 rounded border-slate-300 text-brand-700 focus:ring-brand-600">
                <span>Keep me signed in on this device</span>
            </label>

            <x-button type="submit" class="w-full" x-bind:disabled="submitting">
                <span x-show="!submitting">Sign in</span>
                <span x-cloak x-show="submitting" class="inline-flex items-center gap-2"><svg class="size-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle class="opacity-30" cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3"/><path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>Signing in…</span>
            </x-button>
        </form>
    </section>
</x-layouts.guest>
