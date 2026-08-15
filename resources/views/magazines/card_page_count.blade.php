@if ($pageCountChartData->count() > 4)
    @php
        // See games/card_updates.blade.php: the config is data on the canvas,
        // and resources/js/charts.js draws it.
        $points = $pageCountChartData
            ->map(fn ($data) => ['x' => $data['published'] * 1000, 'y' => $data['count']])
            ->values();

        $config = [
            'type' => 'bar',
            'data' => [
                'datasets' => [[
                    'label'           => 'Page count',
                    'data'            => $points->all(),
                    'backgroundColor' => $points->keys()
                        ->map(fn ($index) => $index % 2 === 0 ? '#c2c2c2' : '#666666')
                        ->all(),
                    'borderColor'     => '#000000',
                    'borderWidth'     => 1,
                ]],
            ],
            'options' => [
                'responsive' => true,
                'legend'     => ['display' => false],
                'scales'     => ['xAxes' => [['type' => 'time']]],
            ],
        ];
    @endphp

    <div class="card bg-dark mb-4">
        <div class="card-header text-center">
            <h2 class="text-uppercase">Page count</h2>
        </div>
        <div class="card-body p-2">
            <p class="card-text">
                This bar chart represents the page count of {{ $magazine->name }}
                over time.
            </p>
            <canvas id="page-count-chart" class="m-auto" data-chart-config='@json($config)'></canvas>
        </div>
    </div>
@endif
