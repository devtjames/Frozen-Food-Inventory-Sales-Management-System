@props(['name', 'label', 'options' => [], 'selected' => null, 'placeholder' => 'Select an option', 'searchPlaceholder' => 'Search options', 'required' => false])

@php
    $id = $attributes->get('id', $name);
    $selectedValue = (string) old($name, $selected);
    $optionData = collect($options)->map(fn ($text, $value) => ['value' => (string) $value, 'label' => (string) $text])->values();
@endphp

<div
    class="relative"
    x-data="{
        open: false,
        query: '',
        selectedValue: @js($selectedValue),
        options: @js($optionData),
        placeholder: @js($placeholder),
        get selectedLabel() { return this.options.find(option => option.value === this.selectedValue)?.label || this.placeholder; },
        get filteredOptions() {
            const needle = this.query.trim().toLocaleLowerCase();
            return needle === '' ? this.options : this.options.filter(option => option.label.toLocaleLowerCase().includes(needle));
        },
        choose(value) {
            this.selectedValue = String(value);
            this.query = this.selectedLabel;
            this.open = false;
            this.$nextTick(() => {
                this.$refs.value.dispatchEvent(new Event('input', { bubbles: true }));
                this.$refs.value.dispatchEvent(new Event('change', { bubbles: true }));
            });
        },
        resetQuery() { this.query = this.selectedLabel; },
    }"
    x-init="resetQuery()"
    @click.outside="open = false; resetQuery()"
    @keydown.escape.window="open = false; resetQuery()"
>
    <label for="{{ $id }}-search" class="form-label">{{ $label }}@if($required)<span class="text-red-600" aria-hidden="true"> *</span>@endif</label>
    <div class="relative">
        <svg viewBox="0 0 24 24" class="pointer-events-none absolute left-3 top-1/2 size-5 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="11" cy="11" r="6"/><path d="m16 16 4 4" stroke-linecap="round"/></svg>
        <input id="{{ $id }}-search" type="search" x-model="query" @focus="open = true; if (query === selectedLabel) query = ''" @input="open = true" @keydown.enter.prevent="if (open && filteredOptions.length) choose(filteredOptions[0].value)" class="form-control cursor-text pl-10 pr-11" placeholder="{{ $searchPlaceholder }}" autocomplete="off" role="combobox" aria-autocomplete="list" :aria-expanded="open" aria-controls="{{ $id }}-options" @if($required) required @endif>
        <button type="button" @click="open = !open; if (open) { query = ''; $nextTick(() => document.getElementById('{{ $id }}-search').focus()) }" class="absolute right-0 top-0 grid size-11 place-items-center text-slate-500" aria-label="Open {{ Str::lower($label) }} options" :aria-expanded="open">
            <svg viewBox="0 0 20 20" class="size-4 transition" :class="open && 'rotate-180'" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.2 7.2a.75.75 0 0 1 1.1 0L10 10.9l3.7-3.7a.75.75 0 1 1 1.1 1.1l-4.2 4.2a.75.75 0 0 1-1.1 0L5.2 8.3a.75.75 0 0 1 0-1.1Z" clip-rule="evenodd"/></svg>
        </button>
    </div>

    <input x-ref="value" type="hidden" name="{{ $name }}" :value="selectedValue" {{ $attributes->except(['id', 'class']) }}>

    <div id="{{ $id }}-options" x-cloak x-show="open" x-transition class="absolute z-40 mt-1 max-h-64 w-full overflow-y-auto rounded-xl border border-slate-200 bg-white p-1 shadow-xl" role="listbox">
        @unless($required)
            <button type="button" @click="choose('')" class="flex min-h-11 w-full items-center rounded-lg px-3 text-left text-sm hover:bg-slate-100" :class="selectedValue === '' && 'bg-brand-50 font-semibold text-brand-800'">{{ $placeholder }}</button>
        @endunless
        <template x-for="option in filteredOptions" :key="option.value">
            <button type="button" @click="choose(option.value)" class="flex min-h-11 w-full items-center justify-between gap-3 rounded-lg px-3 text-left text-sm hover:bg-slate-100" :class="selectedValue === option.value && 'bg-brand-50 font-semibold text-brand-800'" role="option" :aria-selected="selectedValue === option.value">
                <span x-text="option.label"></span>
                <svg x-show="selectedValue === option.value" viewBox="0 0 20 20" class="size-4 shrink-0" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 0 1.4l-8 8a1 1 0 0 1-1.4 0l-4-4a1 1 0 0 1 1.4-1.4L8 12.6l7.3-7.3a1 1 0 0 1 1.4 0Z" clip-rule="evenodd"/></svg>
            </button>
        </template>
        <p x-show="filteredOptions.length === 0" class="px-3 py-4 text-center text-sm text-slate-500">No matching options</p>
    </div>

    @error($name)<p class="form-error">{{ $message }}</p>@enderror
</div>
