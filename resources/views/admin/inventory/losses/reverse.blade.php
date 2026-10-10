<x-layouts.app title="Reverse stock loss">
    <div class="mx-auto max-w-3xl space-y-6">
        <x-page-heading title="Reverse stock loss #{{ $loss->id }}" description="Restore the exact quantities previously deducted from {{ $loss->product->name }}." />
        <x-inventory-nav />

        <x-responsive-card title="Original loss">
            <dl class="grid gap-4 sm:grid-cols-3"><div><dt class="text-sm text-slate-500">Product</dt><dd class="font-semibold">{{ $loss->product->name }}</dd></div><div><dt class="text-sm text-slate-500">Quantity</dt><dd class="font-semibold">{{ $formatter->kilograms($loss->quantity_lost) }}</dd></div><div><dt class="text-sm text-slate-500">Financial value</dt><dd class="font-semibold">{{ $formatter->money($loss->financial_value) }}</dd></div></dl>
        </x-responsive-card>

        <x-responsive-card>
            <form id="loss-reversal-form" method="POST" action="{{ route('admin.inventory.losses.reversal.store', $loss) }}" class="space-y-5">
                @csrf
                <input type="hidden" name="idempotency_key" value="{{ old('idempotency_key', $idempotencyKey) }}">
                <x-textarea-field name="reason" label="Reversal reason" :value="null" rows="4" maxlength="2000" required help="Explain why the original loss should be corrected." />
                <x-input-field name="reversed_at" label="Reversal date and time" type="datetime-local" :value="$defaultReversedAt" required />
            </form>

            <div class="mt-6 flex flex-col-reverse gap-2 border-t border-slate-200 pt-5 sm:flex-row sm:justify-end">
                <x-button href="{{ route('admin.inventory.losses.show', $loss) }}" variant="secondary">Cancel</x-button>
                <x-confirmation-dialog name="reverse-stock-loss" title="Reverse this stock loss?" confirm-text="Restore stock">
                    <x-slot:trigger><x-button type="button" variant="danger" @click="$dispatch('open-confirmation', 'reverse-stock-loss')">Review and reverse</x-button></x-slot:trigger>
                    This creates a separate reversal record and restores {{ $formatter->kilograms($loss->quantity_lost) }} to the exact source batches. The original loss remains visible.
                    <x-slot:confirm><x-button type="submit" form="loss-reversal-form" variant="danger">Restore allocated stock</x-button></x-slot:confirm>
                </x-confirmation-dialog>
            </div>
        </x-responsive-card>
    </div>
</x-layouts.app>
