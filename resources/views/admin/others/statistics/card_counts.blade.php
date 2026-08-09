<h3 id="counts" class="h4 mt-4 mb-3">All counts</h3>

<div class="row">
    @foreach ($counts as $group => $rows)
        <div class="col-12 col-md-6 col-xl-4">
            <div class="card mb-3">
                <div class="card-header">{{ $group }}</div>
                <table class="table table-sm table-striped mb-0">
                    <tbody>
                        @foreach ($rows as $label => $count)
                            <tr>
                                <td>{{ $label }}</td>
                                <td class="text-end">{{ number_format($count) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach
</div>
