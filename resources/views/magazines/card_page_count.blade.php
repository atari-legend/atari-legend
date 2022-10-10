@if ($pageCountChartData->count() > 4)
    <div class="card bg-dark mb-4">
        <div class="card-header text-center">
            <h2 class="text-uppercase">Page count</h2>
        </div>
        <div class="card-body p-2">
            <p class="card-text">
                This bar chart represents the page count of {{ $magazine->name }}
                over time.
            </p>
            <canvas id="page-count-chart" class="m-auto"></canvas>

            <script>
                document.addEventListener("DOMContentLoaded", () => {
                    new Chart('page-count-chart', {
                        type: 'bar',
                        responsive: true,
                        data: {
                            datasets: [{
                                label: 'Page count',
                                data: [
                                    @foreach ($pageCountChartData as $data)
                                    {
                                        x: new Date({{ $data['published'] }} * 1000),
                                        y: {{ $data['count'] }}
                                    },
                                    @endforeach
                                ],
                                backgroundColor: [
                                    @foreach ($pageCountChartData as $data)
                                        @if ($loop->odd)
                                            '#c2c2c2',
                                        @else
                                            '#666666',
                                        @endif
                                    @endforeach
                                ],
                                borderColor: '#000000',
                                borderWidth: 1,
                            }]
                        },
                        options: {
                            legend: {
                                display: false,
                            },
                            scales: {
                                xAxes: [{
                                    type: 'time',

                                }]
                            }
                        }
                    });
                });
            </script>
        </div>
    </div>
@endif
