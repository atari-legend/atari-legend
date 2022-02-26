<div class="card mb-3 bg-light">
    <div class="card-body">
        <h2 class="card-title fs-4">Your recent activity</h2>
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
                            @switch(strtolower($change->action))
                                @case('update')
                                @case('edit')
                                    <i class="far fa-edit text-info"></i>
                                    @break
                                @case('insert')
                                @case('add')
                                    <i class="far fa-plus-square text-success"></i>
                                    @break
                                @case('delete')
                                @case('delete shot')
                                    <i class="far fa-minus-square text-danger"></i>
                                    @break
                                @default
                            @endswitch

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
