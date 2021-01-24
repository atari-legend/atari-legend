<h2 class="card-title fs-4">{{ $softwares->count() }} software</h2>

<div class="card mb-3 bg-light">
    <div class="card-body">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Demozoo</th>
                    <th>Created</th>
                    <th>Updated</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($softwares as $software)

                    <tr>
                        <td><a href="{{ route('admin.menus.software.edit', $software) }}">{{ $software->name }}</a></td>
                        <td>{{ $software->menuSoftwareContentType->name }}</td>
                        <td>
                            @if ($software->demozoo_id)
                                <a href="https://demozoo.org/productions/{{ $software->demozoo_id }}">
                                    <img src="{{ asset('images/demozoo-16x16.png') }}" alt="Demozoo link for {{ $software->name }}">
                                </a>
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $software->created_at ? $software->created_at->diffForHumans() : '-' }}</td>
                        <td>{{ $software->updated_at ? $software->updated_at->diffForHumans() : '-' }}</td>
                        <td>
                            <form action="{{ route('admin.menus.software.destroy', $software) }}"
                                method="POST"
                                onsubmit="javascript:return confirm('This item will be permanently deleted')">
                                @csrf
                                @method('DELETE')
                                <button
                                    @if ($software->menuDiskContents->isNotEmpty())
                                        disabled
                                        title="Software is in use and cannot be deleted"
                                    @else
                                        title="Delete software '{{ $software->name }}'"
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
        <a href="{{ route('admin.menus.software.create') }}" class="btn btn-success">
            <i class="fas fa-plus-square fa-fw"></i> Add a new menu software
        </a>
    </div>
</div>
