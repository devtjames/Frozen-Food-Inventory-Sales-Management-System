@props(['value', 'type', 'formatter'])
@switch($type)
    @case('money'){{ $formatter->money((string) $value) }}@break
    @case('kg'){{ $formatter->kilograms((string) $value) }}@break
    @case('datetime'){{ $formatter->transactionTimestamp($value) }}@break
    @case('date'){{ $formatter->businessDate($value) }}@break
    @default{{ $value }}
@endswitch
