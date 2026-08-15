@php
    // Built here rather than in an inline <script>, so that the config is data
    // and resources/js/charts.js owns the drawing. Chart.js reads `x` as a
    // millisecond timestamp, which is what the seconds from the changelog
    // become.
    $points = collect($updates)
        ->map(fn ($count, $ts) => ['x' => $ts * 1000, 'y' => $count])
        ->values();

    $config = [
        'type' => 'bar',
        'data' => [
            'datasets' => [[
                'label'           => 'Updates',
                'data'            => $points->all(),
                'backgroundColor' => $points->keys()
                    ->map(fn ($index) => $index % 2 === 0 ? '#c2c2c2' : '#666666')
                    ->all(),
                'borderColor'     => '#000000',
                'borderWidth'     => 1,
            ]],
        ],
        'options' => [
            'legend' => ['display' => false],
            'scales' => ['xAxes' => [['type' => 'time']]],
        ],
    ];
@endphp

<div class="card bg-dark mb-4">
    <div class="card-header text-center">
        <h2 class="text-uppercase">Updates</h2>
    </div>
    <div class="card-body p-2">
        <p class="card-text">
            This bar chart represents the number of changes made to the database each
            month over the past year. Hover over the chart for more info.
        </p>
        <canvas id="updates-chart" data-chart-config='@json($config)'></canvas>

        <div class="text-center p-2 mt-1">
            <a href="{{ route('changelog.index') }}">View all database changes</a>
        </div>
    </div>
</div>
