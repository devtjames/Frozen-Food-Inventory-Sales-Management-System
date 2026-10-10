@props(['type' => 'success', 'message' => null])
@php($classes = $type === 'error' ? 'border-red-200 bg-red-50 text-red-800' : 'border-emerald-200 bg-emerald-50 text-emerald-800')
<div x-data="{ show: true }" x-show="show" x-transition role="status" {{ $attributes->class(['flex items-start justify-between gap-3 rounded-xl border p-4 text-sm', $classes]) }}>
    <div class="leading-6">{{ $message ?? $slot }}</div>
    <button type="button" @click="show = false" class="-m-1 grid size-8 shrink-0 place-items-center rounded-lg hover:bg-black/5" aria-label="Dismiss notification">&times;</button>
</div>
