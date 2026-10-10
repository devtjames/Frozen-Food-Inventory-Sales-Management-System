@props(['name' => 'search', 'label' => 'Search', 'placeholder' => 'Search…'])

<div class="relative">
    <label for="{{ $name }}" class="sr-only">{{ $label }}</label>
    <svg class="pointer-events-none absolute left-3.5 top-1/2 size-5 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><circle cx="11" cy="11" r="7" stroke-width="2"/><path d="m20 20-4-4" stroke-width="2" stroke-linecap="round"/></svg>
    <input id="{{ $name }}" name="{{ $name }}" type="search" placeholder="{{ $placeholder }}" {{ $attributes->class(['form-control pl-11']) }}>
</div>
