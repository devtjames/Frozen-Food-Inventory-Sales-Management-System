@props(['label' => 'Data table'])
<div {{ $attributes->class(['card overflow-hidden']) }}>
    @isset($mobile)<div class="divide-y divide-slate-200 md:hidden">{{ $mobile }}</div>@endisset
    <div class="overflow-x-auto {{ isset($mobile) ? 'hidden md:block' : '' }}"><table class="min-w-full divide-y divide-slate-200 text-left text-sm" aria-label="{{ $label }}">{{ $slot }}</table></div>
</div>
