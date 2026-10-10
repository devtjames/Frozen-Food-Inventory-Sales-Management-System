<x-layouts.app title="Staff management">
    <div class="space-y-6">
        <x-page-heading title="Staff management" description="Create and manage cashier access without removing historical staff records.">
            <x-slot:actions><x-button href="{{ route('admin.staff.create') }}">Add cashier</x-button></x-slot:actions>
        </x-page-heading>

        <form method="GET" action="{{ route('admin.staff.index') }}" class="card flex flex-col gap-3 p-4 sm:flex-row sm:items-end">
            <div class="min-w-0 flex-1"><x-search-field name="search" label="Search staff" placeholder="Search name, username, email, or phone" :value="$search" /></div>
            <div class="flex gap-2">
                <x-button type="submit" class="flex-1 sm:flex-none">Search</x-button>
                @if($search)<x-button href="{{ route('admin.staff.index') }}" variant="secondary" class="flex-1 sm:flex-none">Clear</x-button>@endif
            </div>
        </form>

        @if($staff->isEmpty())
            <x-empty-state title="{{ $search ? 'No cashiers match your search' : 'No cashier accounts yet' }}" description="{{ $search ? 'Try a different name, username, email, or phone number.' : 'Create the first cashier account when a staff member is ready to use the system.' }}">
                <x-slot:icon><svg viewBox="0 0 24 24" class="size-6" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M16 20v-1.5a4.5 4.5 0 0 0-4.5-4.5h-4A4.5 4.5 0 0 0 3 18.5V20M9.5 10a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM16 8h5M18.5 5.5v5" stroke-linecap="round"/></svg></x-slot:icon>
                @if(! $search)
                    <x-slot:action>
                        <x-button href="{{ route('admin.staff.create') }}">Add cashier</x-button>
                    </x-slot:action>
                @endif
            </x-empty-state>
        @else
            <x-responsive-table label="Cashier accounts">
                <x-slot:mobile>
                    @foreach($staff as $cashier)
                        <article class="p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0"><h2 class="truncate font-bold text-slate-950">{{ $cashier->name }}</h2><p class="truncate text-sm text-slate-600">{{ $cashier->username ? '@'.$cashier->username.' · ' : '' }}{{ $cashier->email }}</p></div>
                                <x-status-badge :variant="$cashier->is_active ? 'success' : 'danger'">{{ $cashier->is_active ? 'Active' : 'Inactive' }}</x-status-badge>
                            </div>
                            <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
                                <div><dt class="text-slate-500">Phone</dt><dd class="mt-0.5 font-medium text-slate-800">{{ $cashier->phone ?: 'Not provided' }}</dd></div>
                                <div><dt class="text-slate-500">Registered</dt><dd class="mt-0.5 font-medium text-slate-800">{{ $cashier->created_at->timezone(config('app.business_timezone'))->format('d M Y') }}</dd></div>
                                <div><dt class="text-slate-500">Role</dt><dd class="mt-0.5 font-medium text-slate-800">{{ $cashier->role->label() }}</dd></div>
                            </dl>
                            <div class="mt-4 flex flex-wrap gap-2"><x-button href="{{ route('admin.staff.show', $cashier) }}" variant="secondary">View</x-button><x-button href="{{ route('admin.staff.edit', $cashier) }}" variant="secondary">Edit</x-button><x-button href="{{ route('admin.staff.password.edit', $cashier) }}" variant="secondary">Reset password</x-button>@include('admin.staff._status-action', ['staff' => $cashier, 'dialogPrefix' => 'mobile'])</div>
                        </article>
                    @endforeach
                </x-slot:mobile>

                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-5 py-3 font-semibold">Staff member</th><th class="px-5 py-3 font-semibold">Phone</th><th class="px-5 py-3 font-semibold">Role</th><th class="px-5 py-3 font-semibold">Status</th><th class="px-5 py-3 font-semibold">Registered</th><th class="px-5 py-3 text-right font-semibold">Actions</th></tr></thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    @foreach($staff as $cashier)
                        <tr>
                            <td class="px-5 py-4"><p class="font-semibold text-slate-900">{{ $cashier->name }}</p><p class="mt-0.5 text-xs text-slate-500">{{ $cashier->username ? '@'.$cashier->username.' · ' : '' }}{{ $cashier->email }}</p></td>
                            <td class="px-5 py-4 text-slate-600">{{ $cashier->phone ?: 'Not provided' }}</td>
                            <td class="px-5 py-4 text-slate-600">{{ $cashier->role->label() }}</td>
                            <td class="px-5 py-4"><x-status-badge :variant="$cashier->is_active ? 'success' : 'danger'">{{ $cashier->is_active ? 'Active' : 'Inactive' }}</x-status-badge></td>
                            <td class="px-5 py-4 text-slate-600">{{ $cashier->created_at->timezone(config('app.business_timezone'))->format('d M Y') }}</td>
                            <td class="px-5 py-4"><div class="flex flex-wrap justify-end gap-2"><x-button href="{{ route('admin.staff.show', $cashier) }}" variant="secondary">View</x-button><x-button href="{{ route('admin.staff.edit', $cashier) }}" variant="secondary">Edit</x-button><x-button href="{{ route('admin.staff.password.edit', $cashier) }}" variant="secondary">Reset</x-button>@include('admin.staff._status-action', ['staff' => $cashier, 'dialogPrefix' => 'desktop'])</div></td>
                        </tr>
                    @endforeach
                </tbody>
            </x-responsive-table>
            <x-pagination :paginator="$staff" />
        @endif
    </div>
</x-layouts.app>
