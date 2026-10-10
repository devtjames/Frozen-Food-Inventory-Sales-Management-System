@props(['profile' => null])

@php($profile ??= app(\App\Services\BusinessSettingsService::class)->receiptProfile())
<header {{ $attributes->class(['text-center']) }}>
    @if($profile['show_logo'] && $profile['logo_url'])
        <img src="{{ $profile['logo_url'] }}" alt="{{ $profile['business_name'] }} logo" class="mx-auto mb-3 max-h-20 max-w-40 object-contain">
    @endif
    <h1 class="text-lg font-bold text-slate-950">{{ $profile['business_name'] }}</h1>
    @if($profile['address'])<p class="mt-1 whitespace-pre-line text-sm text-slate-600">{{ $profile['address'] }}</p>@endif
    @if($profile['phone'])<p class="text-sm text-slate-600">{{ $profile['phone'] }}</p>@endif
</header>
