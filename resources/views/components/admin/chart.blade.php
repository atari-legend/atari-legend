@props([
    'id',
    'title' => null,
    'type' => 'bar',
    'labels' => [],
    'data' => null,
    'datasets' => null,
    'label' => '',
    'horizontal' => false,
    'stacked' => false,
    'height' => 260,
    'footnote' => null,
])

@php
    // Bootstrap's theme colours, cycled for multi-series and doughnut charts.
    $palette = ['#0d6efd', '#198754', '#dc3545', '#ffc107', '#0dcaf0', '#6610f2', '#fd7e14', '#20c997', '#d63384', '#6c757d'];

    $sliced = in_array($type, ['doughnut', 'pie']);

    if ($datasets !== null) {
        $chartDatasets = collect($datasets)->values()->map(fn ($dataset, $index) => $dataset + [
            'backgroundColor' => $palette[$index % count($palette)],
            'borderColor'     => '#ffffff',
            'borderWidth'     => 1,
        ])->all();
    } else {
        $chartDatasets = [[
            'label'           => $label,
            'data'            => array_values($data ?? []),
            'backgroundColor' => $sliced
                ? collect($labels)->keys()->map(fn ($index) => $palette[$index % count($palette)])->all()
                : $palette[0],
            'borderColor'     => '#ffffff',
            'borderWidth'     => 1,
        ]];
    }

    $options = [
        'responsive'          => true,
        'maintainAspectRatio' => false,
        'legend'              => [
            'display'  => $sliced || count($chartDatasets) > 1,
            'position' => 'bottom',
        ],
    ];

    if (! $sliced) {
        // A horizontal bar chart swaps the two axes over, so work out which one
        // carries the categories before setting the ticks up.
        $categoryAxis = $horizontal ? 'yAxes' : 'xAxes';
        $valueAxis = $horizontal ? 'xAxes' : 'yAxes';

        $options['scales'] = [
            $categoryAxis => [['stacked' => $stacked, 'ticks' => ['autoSkip' => false]]],
            $valueAxis    => [['stacked' => $stacked, 'ticks' => ['beginAtZero' => true]]],
        ];
    }

    $config = [
        'type'    => $horizontal ? 'horizontalBar' : $type,
        'data'    => ['labels' => array_values($labels), 'datasets' => $chartDatasets],
        'options' => $options,
    ];
@endphp

<div class="card mb-3">
    @if ($title)
        <div class="card-header">{{ $title }}</div>
    @endif
    <div class="card-body">
        <div style="height: {{ $height }}px">
            {{-- Drawn by resources/js/charts.js, which picks up every canvas
                 carrying a config. --}}
            <canvas id="{{ $id }}" data-chart-config='@json($config)'></canvas>
        </div>
        @if ($footnote)
            <p class="card-text text-muted small mt-2 mb-0">{{ $footnote }}</p>
        @endif
    </div>
</div>
