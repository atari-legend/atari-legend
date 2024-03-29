<div class="card bg-dark mb-4">
    <div class="card-header text-center">
        <h2 class="text-uppercase">Updates</h2>
    </div>
    <div class="card-body p-2">
        <p class="card-text">
            This bar chart represents the number of changes made to the database each
            month over the past year. Hover over the chart for more info.
        </p>
        <canvas id="updates-chart"></canvas>

        <div class="text-center p-2 mt-1">
            <a href="{{ route('changelog.index') }}">View all database changes</a>
        </div>

        <script>
            document.addEventListener("DOMContentLoaded", () => {
                new Chart('updates-chart', {
                    type: 'bar',
                    data: {
                        datasets: [{
                            label: 'Updates',
                            data: [
                                @foreach ($updates as $ts => $count)
                                {
                                    x: new Date({{ $ts }} * 1000),
                                    y: {{ $count }}
                                },
                                @endforeach
                            ],
                            backgroundColor: [
                                @foreach ($updates as $update)
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
                                type: 'time'
                            }]
                        }
                    }
                });
            });
        </script>
    </div>
</div>
