@props(['name', 'label', 'value' => null, 'help' => null, 'required' => false, 'rows' => 4])

@php($id = $attributes->get('id', $name))
<div>
    <label for="{{ $id }}" class="form-label">{{ $label }}@if($required)<span class="text-red-600" aria-hidden="true"> *</span>@endif</label>
    <textarea id="{{ $id }}" name="{{ $name }}" rows="{{ $rows }}" @if($required) required @endif @if($errors->has($name)) aria-invalid="true" aria-describedby="{{ $id }}-error" @elseif($help) aria-describedby="{{ $id }}-help" @endif {{ $attributes->except(['id'])->class(['form-control', 'border-red-400 focus:border-red-600 focus:ring-red-100' => $errors->has($name)]) }}>{{ old($name, $value) }}</textarea>
    @if($help && ! $errors->has($name))<p id="{{ $id }}-help" class="mt-1.5 text-sm text-slate-500">{{ $help }}</p>@endif
    @error($name)<p id="{{ $id }}-error" class="form-error">{{ $message }}</p>@enderror
</div>
