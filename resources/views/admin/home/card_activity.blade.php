<div class="card mb-3 bg-light">
    <div class="card-body">
        <h2 class="card-title fs-4">
            Your recent activity
            <a href="{{ route('admin.others.changelog.index') }}" class="fs-6 ms-2">See all</a>
        </h2>
        <table class="table table-striped table-sm">
            <thead>
                <tr>
                    <th>Action</th>
                    <th>Section</th>
                    <th>Sub-section</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($changes as $change)
                    <tr>
                        <td>
                            @include('admin.others.changelog.datatable_action', ['action' => $change->action])
                        </td>
                        <td>
                            <span class="text-muted">{{ $change->section }}:</span>
                            {{ $change->section_name }}
                        </td>
                        <td>
                            <span class="text-muted">{{ $change->sub_section }}:</span>
                            {{ $change->sub_section_name }}</td>
                        <td>
                            <abbr title="{{ $change->timestamp->format('F j, Y H:i') }}">
                                {{ $change->timestamp->diffForHumans() }}
                            </abbr>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
