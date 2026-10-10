@props(['compact' => false, 'settings' => null, 'logoUrl' => null])

<div {{ $attributes->class(['flex items-center gap-3']) }}>
    @if($logoUrl)
        <span class="grid size-10 shrink-0 place-items-center overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm"><img src="{{ $logoUrl }}" alt="" class="size-full object-contain p-1"></span>
    @else
        <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-brand-700 text-white shadow-sm" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" class="size-6" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 2v20M4.8 6.2l14.4 11.6M19.2 6.2 4.8 17.8M8.5 4 12 6l3.5-2M8.5 20l3.5-2 3.5 2M3.5 10 7 12l-3.5 2M20.5 10 17 12l3.5 2"/></svg>
        </span>
    @endif
    @unless($compact)
        <span class="min-w-0 leading-tight"><span class="block truncate font-bold text-slate-900">{{ $settings?->business_name ?? config('app.name') }}</span><span class="block truncate text-xs text-slate-500">Inventory &amp; Sales</span></span>
    @endunless
</div>
