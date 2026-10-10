@props(['variant' => 'neutral'])

@php
    $classes = match ($variant) {
        'success' => 'bg-emerald-100 text-emerald-800 ring-emerald-200',
        'warning' => 'bg-amber-100 text-amber-800 ring-amber-200',
        'danger' => 'bg-red-100 text-red-800 ring-red-200',
        'info' => 'bg-sky-100 text-sky-800 ring-sky-200',
        default => 'bg-slate-100 text-slate-700 ring-slate-200',
    };
@endphp
<span {{ $attributes->class(['inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset', $classes]) }}>{{ $slot }}</span>
