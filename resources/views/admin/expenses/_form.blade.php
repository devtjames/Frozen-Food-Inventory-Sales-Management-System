@php($editing = isset($expense))
<div class="space-y-5">
    <x-searchable-dropdown name="expense_category_id" label="Expense category" :options="$categories" :selected="$expense->expense_category_id ?? null" placeholder="Select a category" search-placeholder="Search categories" required />
    <x-input-field name="description" label="Description" :value="$expense->description ?? null" maxlength="255" required />
    <x-input-field name="amount" label="Amount (₦)" type="number" :value="$expense->amount ?? null" min="0.01" step="0.01" inputmode="decimal" required />
    <x-input-field name="expense_date" label="Expense date" type="date" :value="$editing ? $expense->expense_date->format('Y-m-d') : $defaultDate" required />
    <x-textarea-field name="note" label="Note" :value="$expense->note ?? null" rows="4" maxlength="2000" help="Optional supporting information about this expense." />
    @if($editing)<div class="rounded-xl border border-amber-200 bg-amber-50 p-4"><x-textarea-field name="correction_reason" label="Correction reason" :value="null" rows="3" maxlength="2000" required help="Required. This reason and every changed value will be retained in the audit history." /></div>@endif
</div>
