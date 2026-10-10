@props(['title' => null])
<section {{ $attributes->class(['card overflow-hidden']) }}>
    @if($title || isset($header))<header class="border-b border-slate-200 px-4 py-4 sm:px-6">{{ $header ?? '' }}@if($title)<h2 class="font-bold text-slate-900">{{ $title }}</h2>@endif</header>@endif
    <div class="p-4 sm:p-6">{{ $slot }}</div>
</section>
