@props(['profile' => null])

@php($profile ??= app(\App\Services\BusinessSettingsService::class)->receiptProfile())
@if($profile['receipt_footer_message'])
    <footer {{ $attributes->class(['whitespace-pre-line text-center text-sm text-slate-600']) }}>{{ $profile['receipt_footer_message'] }}</footer>
@endif
