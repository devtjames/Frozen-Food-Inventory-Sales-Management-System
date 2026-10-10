@props(['name', 'label', 'options' => [], 'selected' => null, 'placeholder' => 'Select an option', 'required' => false])

@php($id = $attributes->get('id', $name))
<div>
    <label for="{{ $id }}" class="form-label">{{ $label }}@if($required)<span class="text-red-600"> *</span>@endif</label>
    <select id="{{ $id }}" name="{{ $name }}" @if($required) required @endif {{ $attributes->except(['id'])->class(['form-control']) }}>
        <option value="">{{ $placeholder }}</option>
        @foreach($options as $value => $text)<option value="{{ $value }}" @selected((string) old($name, $selected) === (string) $value)>{{ $text }}</option>@endforeach
    </select>
    @error($name)<p class="form-error">{{ $message }}</p>@enderror
</div>
