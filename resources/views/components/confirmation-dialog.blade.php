@props(['name', 'title' => 'Please confirm', 'confirmText' => 'Confirm', 'cancelText' => 'Cancel'])
<div x-data="{ open: false }" @open-confirmation.window="if ($event.detail === '{{ $name }}') open = true">
    {{ $trigger ?? '' }}
    <div x-cloak x-show="open" @keydown.escape.window="open = false" class="fixed inset-0 z-50 grid place-items-end p-0 sm:place-items-center sm:p-4" role="dialog" aria-modal="true" aria-labelledby="{{ $name }}-title">
        <div x-show="open" x-transition.opacity class="absolute inset-0 bg-slate-950/50" @click="open = false"></div>
        <div x-show="open" x-transition class="relative w-full rounded-t-2xl bg-white p-5 shadow-xl sm:max-w-md sm:rounded-2xl sm:p-6">
            <h2 id="{{ $name }}-title" class="text-lg font-bold text-slate-950">{{ $title }}</h2>
            <div class="mt-2 text-sm leading-6 text-slate-600">{{ $slot }}</div>
            <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end"><x-button variant="secondary" @click="open = false">{{ $cancelText }}</x-button>{{ $confirm }}</div>
        </div>
    </div>
</div>
