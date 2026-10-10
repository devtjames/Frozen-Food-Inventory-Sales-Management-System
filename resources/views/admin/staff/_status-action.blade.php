@if($staff->is_active)
    @php($dialogName = ($dialogPrefix ?? 'details').'-deactivate-'.$staff->id)
    <x-confirmation-dialog :name="$dialogName" title="Deactivate {{ $staff->name }}?">
        <x-slot:trigger>
            <x-button variant="danger" @click="$dispatch('open-confirmation', '{{ $dialogName }}')">Deactivate</x-button>
        </x-slot:trigger>
        This cashier will be signed out and unable to access protected pages until an administrator activates the account again. Their staff record will be preserved.
        <x-slot:confirm>
            <form method="POST" action="{{ route('admin.staff.status.update', $staff) }}" x-data="{ submitting: false }" @submit="submitting = true">
                @csrf
                @method('PATCH')
                <input type="hidden" name="is_active" value="0">
                <x-button type="submit" variant="danger" x-bind:disabled="submitting"><span x-show="!submitting">Deactivate</span><span x-cloak x-show="submitting">Deactivating…</span></x-button>
            </form>
        </x-slot:confirm>
    </x-confirmation-dialog>
@else
    <form method="POST" action="{{ route('admin.staff.status.update', $staff) }}" x-data="{ submitting: false }" @submit="submitting = true">
        @csrf
        @method('PATCH')
        <input type="hidden" name="is_active" value="1">
        <x-button type="submit" x-bind:disabled="submitting"><span x-show="!submitting">Activate</span><span x-cloak x-show="submitting">Activating…</span></x-button>
    </form>
@endif
