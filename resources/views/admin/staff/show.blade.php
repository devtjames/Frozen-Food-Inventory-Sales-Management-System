<x-layouts.app title="Cashier details">
    <div class="mx-auto max-w-4xl space-y-6">
        <x-page-heading title="{{ $staff->name }}" description="Cashier account information and access controls.">
            <x-slot:actions><x-button href="{{ route('admin.staff.edit', $staff) }}" variant="secondary">Edit details</x-button><x-button href="{{ route('admin.staff.password.edit', $staff) }}" variant="secondary">Reset password</x-button></x-slot:actions>
        </x-page-heading>

        <x-responsive-card>
            <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
                <dl class="grid min-w-0 flex-1 gap-5 sm:grid-cols-2">
                    <div><dt class="text-sm font-medium text-slate-500">Full name</dt><dd class="mt-1 font-semibold text-slate-950">{{ $staff->name }}</dd></div>
                    <div><dt class="text-sm font-medium text-slate-500">Account status</dt><dd class="mt-1"><x-status-badge :variant="$staff->is_active ? 'success' : 'danger'">{{ $staff->is_active ? 'Active' : 'Inactive' }}</x-status-badge></dd></div>
                    <div><dt class="text-sm font-medium text-slate-500">Email address</dt><dd class="mt-1 break-all text-slate-800">{{ $staff->email }}</dd></div>
                    <div><dt class="text-sm font-medium text-slate-500">Username</dt><dd class="mt-1 text-slate-800">{{ $staff->username ? '@'.$staff->username : 'Not set' }}</dd></div>
                    <div><dt class="text-sm font-medium text-slate-500">Phone number</dt><dd class="mt-1 text-slate-800">{{ $staff->phone ?: 'Not provided' }}</dd></div>
                    <div><dt class="text-sm font-medium text-slate-500">Role</dt><dd class="mt-1 text-slate-800">{{ $staff->role->label() }}</dd></div>
                    <div><dt class="text-sm font-medium text-slate-500">Registration date</dt><dd class="mt-1 text-slate-800">{{ $staff->created_at->timezone(config('app.business_timezone'))->format('d M Y, g:i A') }}</dd></div>
                    <div><dt class="text-sm font-medium text-slate-500">Last updated</dt><dd class="mt-1 text-slate-800">{{ $staff->updated_at->timezone(config('app.business_timezone'))->format('d M Y, g:i A') }}</dd></div>
                </dl>
            </div>
        </x-responsive-card>

        <x-responsive-card title="Account access">
            <p class="mb-4 text-sm leading-6 text-slate-600">Deactivation preserves this staff record and prevents login or continued access. Password resets sign out existing sessions.</p>
            @include('admin.staff._status-action', ['staff' => $staff, 'dialogPrefix' => 'details'])
        </x-responsive-card>
    </div>
</x-layouts.app>
