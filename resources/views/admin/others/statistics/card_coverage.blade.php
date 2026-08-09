<h3 id="coverage" class="h4 mt-4 mb-3">Coverage</h3>

<p class="text-muted">
    How complete the database is. The lower the bar, the more entries are still missing that
    piece of information.
</p>

<div class="row">
    @foreach ($coverage as $group => $rows)
        <div class="col-12 col-xl-4">
            <div class="card mb-3">
                <div class="card-header">{{ $group }}</div>
                <table class="table table-sm table-striped mb-0">
                    <tbody>
                        @foreach ($rows as $row)
                            @php
                                $variant = $row['percent'] < 33 ? 'bg-danger' : ($row['percent'] < 66 ? 'bg-warning' : 'bg-success');
                            @endphp
                            <tr>
                                <td>{{ $row['label'] }}</td>
                                <td class="text-end text-nowrap">
                                    {{ number_format($row['count']) }}
                                    <span class="text-muted">/ {{ number_format($row['total']) }}</span>
                                </td>
                                <td class="text-end text-nowrap" style="width: 4rem">{{ $row['percent'] }}%</td>
                                <td style="width: 30%">
                                    <div class="progress" style="height: 0.75rem"
                                        role="progressbar"
                                        aria-label="{{ $row['label'] }}"
                                        aria-valuenow="{{ $row['percent'] }}"
                                        aria-valuemin="0" aria-valuemax="100">
                                        <div class="progress-bar {{ $variant }}" style="width: {{ $row['percent'] }}%"></div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach
</div>
