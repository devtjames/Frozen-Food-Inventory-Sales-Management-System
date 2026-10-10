@props(['title', 'description' => null])
<div {{ $attributes->class(['card px-5 py-10 text-center sm:px-8']) }}>
    <div class="mx-auto grid size-12 place-items-center rounded-2xl bg-brand-50 text-brand-700">{{ $icon ?? '—' }}</div>
    <h2 class="mt-4 text-base font-bold text-slate-900">{{ $title }}</h2>
    @if($description)<p class="mx-auto mt-1.5 max-w-lg text-sm leading-6 text-slate-600">{{ $description }}</p>@endif
    @isset($action)<div class="mt-5">{{ $action }}</div>@endisset
</div>
