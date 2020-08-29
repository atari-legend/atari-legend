<div class="card bg-dark mb-4">
    <div class="card-header text-center">
        <h2 class="text-uppercase">Updates</h2>
    </div>
    <div class="card-body p-2">
        <p class="card-text">
            This bar chart represents the nr of changes made to the database each
            month over the past year. Hover over the chart for more info. For all
            the details of the changes made, please visit the <!-- FIXME -->
            <a href="#">change log</a> section of the control panel.
        </p>
        <canvas id="updates-chart"></canvas>

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
