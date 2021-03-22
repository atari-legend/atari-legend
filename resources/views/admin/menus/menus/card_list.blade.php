<h2 class="card-title fs-4">{{ $menus->total() }} menus in this set</h2>

<div class="card mb-3 bg-light">
    <div class="card-body">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Label</th>
                    <th>Released</th>
                    <th>Disks</th>
                    <th>Notes</th>
                    <th>Created</th>
                    <th>Updated</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($menus as $menu)
                    <tr>
                        <td><a href="{{ route('admin.menus.menus.edit', $menu) }}">{{ $menu->label }}</a></td>
                        <td>
                            @if ($menu->date !== null)
                                {{ $menu->date->format('F j, Y') }}
                            @endif
                        </td>
                        <td>{{ $menu->disks->count() }}</td>
                        <td>{{ Str::limit($menu->notes, 30) }}</td>
                        <td>{{ $menu->created_at ? $menu->created_at->diffForHumans() : '-' }}</td>
                        <td>{{ $menu->updated_at ? $menu->updated_at->diffForHumans() : '-' }}</td>
                        <td>
                            <form action="{{ route('admin.menus.menus.destroy', $menu) }}"
                                method="POST"
                                onsubmit="javascript:return confirm('This item will be permanently deleted')">
                                @csrf
                                @method('DELETE')
                                <button title="Delete menu '{{ $menu->label }}'" class="btn">
                                    <i class="fas fa-trash fa-fw text-danger" aria-hidden="true"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        {{ $menus->links('admin.layouts.pagination.bootstrap-5') }}
        <a href="{{ route('admin.menus.menus.create', ['set' => $set]) }}" class="btn btn-success">
            <i class="fas fa-plus-square fa-fw"></i> Add a new menu to this set
        </a>
    </div>
</div>
