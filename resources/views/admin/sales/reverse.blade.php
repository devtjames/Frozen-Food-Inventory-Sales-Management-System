<x-layouts.app title="Reverse sale">
    <div class="mx-auto max-w-3xl space-y-6">
        <x-page-heading title="Reverse sale" description="{{ $sale->transaction_number }}" />
        <x-responsive-card title="Original transaction"><dl class="grid gap-4 sm:grid-cols-3"><div><dt class="text-sm text-slate-500">Cashier</dt><dd class="font-semibold">{{ $sale->cashier->name }}</dd></div><div><dt class="text-sm text-slate-500">Items</dt><dd class="font-semibold">{{ $sale->items->count() }}</dd></div><div><dt class="text-sm text-slate-500">Full refund</dt><dd class="font-bold text-brand-900">{{ $formatter->money($sale->total_amount) }}</dd></div></dl></x-responsive-card>
        <x-responsive-card>
            <form id="sale-reversal-form" method="POST" action="{{ route('admin.sales.reversal.store', $sale) }}" class="space-y-5">@csrf<input type="hidden" name="idempotency_key" value="{{ old('idempotency_key', $idempotencyKey) }}"><x-dropdown name="stock_action" label="Stock treatment" :options="$stockActions" :selected="null" placeholder="Select what happened to the products" required /><p class="-mt-3 text-sm text-slate-500">Restore only when the original sale was a mistake or all returned products were verified as suitable for resale. Otherwise inventory stays deducted.</p><x-dropdown name="refund_method" label="Refund method" :options="$paymentMethods" :selected="$sale->payment_method->value" required /><x-textarea-field name="reason" label="Reversal reason" :value="null" rows="4" maxlength="2000" required help="Explain why the transaction is being reversed and the chosen stock treatment." /><x-input-field name="reversed_at" label="Reversal and refund date" type="datetime-local" :value="$defaultReversedAt" required /></form>
            <div class="mt-6 flex flex-col-reverse gap-2 border-t border-slate-200 pt-5 sm:flex-row sm:justify-end">
                <x-button href="{{ route('admin.sales.show', $sale) }}" variant="secondary">Cancel</x-button>
                <x-confirmation-dialog name="reverse-sale" title="Reverse and refund this sale?" confirm-text="Reverse sale">
                    <x-slot:trigger>
                        <x-button type="button" variant="danger" @click="$dispatch('open-confirmation', 'reverse-sale')">Review reversal</x-button>
                    </x-slot:trigger>

                    The original sale and payment will remain in the audit history. A full refund of {{ $formatter->money($sale->total_amount) }} will be recorded. Stock changes follow the selected treatment.

                    <x-slot:confirm>
                        <x-button type="submit" form="sale-reversal-form" variant="danger">Confirm reversal</x-button>
                    </x-slot:confirm>
                </x-confirmation-dialog>
            </div>
        </x-responsive-card>
    </div>
</x-layouts.app>
