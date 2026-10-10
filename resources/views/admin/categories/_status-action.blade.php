@if($category->is_active)
    @php($dialogName = ($dialogPrefix ?? 'details').'-deactivate-category-'.$category->id)
    <x-confirmation-dialog :name="$dialogName" title="Deactivate {{ $category->name }}?">
        <x-slot:trigger>
            <x-button variant="danger" @click="$dispatch('open-confirmation', '{{ $dialogName }}')">Deactivate</x-button>
        </x-slot:trigger>
        The category and all current or future product records will be preserved. Products in this category will be excluded from new POS transactions until it is reactivated.
        <x-slot:confirm>
            <form method="POST" action="{{ route('admin.categories.status.update', $category) }}" x-data="{ submitting: false }" @submit="submitting = true">
                @csrf
                @method('PATCH')
                <input type="hidden" name="is_active" value="0">
                <x-button type="submit" variant="danger" x-bind:disabled="submitting"><span x-show="!submitting">Deactivate</span><span x-cloak x-show="submitting"><x-loading-indicator label="Deactivating…" /></span></x-button>
            </form>
        </x-slot:confirm>
    </x-confirmation-dialog>
@else
    <form method="POST" action="{{ route('admin.categories.status.update', $category) }}" x-data="{ submitting: false }" @submit="submitting = true">
        @csrf
        @method('PATCH')
        <input type="hidden" name="is_active" value="1">
        <x-button type="submit" x-bind:disabled="submitting"><span x-show="!submitting">Activate</span><span x-cloak x-show="submitting"><x-loading-indicator label="Activating…" /></span></x-button>
    </form>
@endif
