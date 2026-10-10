@props(['types', 'active'])
<nav aria-label="Reports" class="flex gap-2 overflow-x-auto pb-1">
    @foreach($types as $type)
        <a href="{{ route('admin.reports.show', $type->value) }}" class="inline-flex min-h-11 shrink-0 items-center rounded-xl px-4 text-sm font-semibold {{ $active === $type ? 'bg-brand-700 text-white' : 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50' }}">{{ $type->label() }}</a>
    @endforeach
</nav>
