@props(['id', 'title', 'data', 'type' => 'bar'])
<x-responsive-card :title="$title">
    @if(count($data['labels']))
        <div class="relative h-64 w-full sm:h-72"><canvas id="{{ $id }}" aria-label="{{ $title }}" role="img"></canvas></div>
        @push('scripts')
            <script>document.addEventListener('DOMContentLoaded', () => { const element = document.getElementById(@js($id)); if (!element || !window.Chart) return; new window.Chart(element, {type: @js($type), data: {labels: @js($data['labels']), datasets: [{label: 'Revenue (₦)', data: @js($data['values']), backgroundColor: @js($type === 'line' ? 'rgba(23, 102, 58, 0.18)' : 'rgba(23, 102, 58, 0.78)'), borderColor: '#17663a', borderWidth: 2, fill: @js($type === 'line'), tension: 0.25}]}, options: {responsive: true, maintainAspectRatio: false, plugins: {legend: {display: false}}, scales: {y: {beginAtZero: true, ticks: {callback: value => '₦' + Number(value).toLocaleString()}}}}}); });</script>
        @endpush
    @else
        <div class="grid h-48 place-items-center text-center text-sm text-slate-500">No valid sales in this period.</div>
    @endif
</x-responsive-card>
