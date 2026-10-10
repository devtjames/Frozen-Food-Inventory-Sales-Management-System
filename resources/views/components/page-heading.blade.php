@props(['title', 'description' => null])

<div {{ $attributes->class(['flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between']) }}>
    <div class="min-w-0"><h1 class="text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">{{ $title }}</h1>@if($description)<p class="mt-1.5 max-w-2xl text-sm leading-6 text-slate-600 sm:text-base">{{ $description }}</p>@endif</div>
    @isset($actions)<div class="flex shrink-0 flex-wrap gap-2">{{ $actions }}</div>@endisset
</div>
