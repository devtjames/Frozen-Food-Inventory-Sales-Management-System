@php($editing = isset($staff))

<div class="grid gap-5 sm:grid-cols-2">
    <div class="sm:col-span-2">
        <x-input-field name="name" label="Full name" :value="$staff->name ?? null" autocomplete="name" required />
    </div>
    <x-input-field name="email" label="Email address" type="email" :value="$staff->email ?? null" autocomplete="email" inputmode="email" required />
    <x-input-field name="username" label="Username" :value="$staff->username ?? null" autocomplete="username" autocapitalize="none" spellcheck="false" help="Optional. Use letters, numbers, dashes, or underscores." />
    <div class="sm:col-span-2">
        <x-input-field name="phone" label="Phone number" type="tel" :value="$staff->phone ?? null" autocomplete="tel" inputmode="tel" help="Optional. Include the country code where appropriate." />
    </div>

    @unless($editing)
        <x-input-field name="password" label="Password" type="password" autocomplete="new-password" help="Use 12+ characters with uppercase, lowercase, number, and symbol." required />
        <x-input-field name="password_confirmation" label="Confirm password" type="password" autocomplete="new-password" required />
        <div class="sm:col-span-2">
            <x-dropdown name="is_active" label="Account status" :options="['1' => 'Active', '0' => 'Inactive']" selected="1" required />
        </div>
    @endunless
</div>
