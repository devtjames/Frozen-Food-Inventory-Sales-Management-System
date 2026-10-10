@props(['href' => null, 'type' => 'button', 'variant' => 'primary'])

@php
    $classes = match ($variant) {
        'secondary' => 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50',
        'danger' => 'border border-red-700 bg-red-700 text-white hover:bg-red-800',
        'ghost' => 'border border-transparent bg-transparent text-slate-700 hover:bg-slate-100',
        default => 'border border-brand-700 bg-brand-700 text-white hover:bg-brand-800',
    };
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->class(['inline-flex min-h-11 items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold shadow-sm transition disabled:pointer-events-none disabled:opacity-60', $classes]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->class(['inline-flex min-h-11 items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold shadow-sm transition disabled:pointer-events-none disabled:opacity-60', $classes]) }}>{{ $slot }}</button>
@endif
