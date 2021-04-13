<h2 class="card-title fs-4">{{ $sets->total() }} Menu sets</h2>

<div class="card mb-3 bg-light">
    <div class="card-body">

        <div class="row mb-2 text-center">
            <ul class="pagination justify-content-center">
                <li class="page-item @if (!isset($letter)) active @endif">
                    <a class="page-link"
                        href="{{ route('admin.menus.sets.index') }}">All</a>
                </li>
                <li class="page-item @if (isset($letter) && $letter === '0-9') active @endif">
                    <a class="page-link"
                        href="{{ route('admin.menus.sets.index', ['letter' => '0-9']) }}">#</a>
                </li>
                @foreach (range('A', 'Z') as $l)
                    <li class="page-item @if (isset($letter) && $letter === $l) active @endif">
                        <a class="page-link"
                            href="{{ route('admin.menus.sets.index', ['letter' => $l]) }}">{{ $l }}</a>
                    </li>
                @endforeach
            </ul>
        </div>


        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Crew(s)</th>
                    <th>Menus</th>
                    <th>Created</th>
                    <th>Updated</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($sets as $set)
                    <tr>
                        <td><a href="{{ route('admin.menus.sets.edit', $set) }}">{{ $set->name }}</a></td>
                        <td>
                            {{ $set->crews()->pluck('crew_name')->join(', ') }}
                        </td>
                        <td>{{ $set->menus()->count() }}</td>
                        <td>{{ $set->created_at ? $set->created_at->diffForHumans() : '-' }}</td>
                        <td>{{ $set->updated_at ? $set->updated_at->diffForHumans() : '-' }}</td>
                        <td>
                            <form action="{{ route('admin.menus.sets.destroy', $set) }}"
                                method="POST"
                                onsubmit="javascript:return confirm('This item will be permanently deleted')">
                                @csrf
                                @method('DELETE')
                                <button
                                    @if ($set->menus->isNotEmpty())
                                        disabled
                                        title="Please delete all menus of this set first"
                                    @else
                                        title="Delete set '{{ $set->name }}'"
                                    @endif
                                    class="btn">
                                    <i class="fas fa-trash fa-fw text-danger" aria-hidden="true"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        {{ $sets->links('admin.layouts.pagination.bootstrap-5') }}
        <a href="{{ route('admin.menus.sets.create') }}" class="btn btn-success">
            <i class="fas fa-plus-square fa-fw"></i> Add a new menu set
        </a>
    </div>
</div>
