<div class="card mb-3 bg-light">
    <div class="card-body">
        <h2 class="card-title fs-4">Menu content types</h2>
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Menu disks count</th>
                    <th>Created</th>
                    <th>Updated</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($contentTypes as $contentType)

                    <tr>
                        <td><a href="{{ route('admin.menus.content-types.edit', $contentType) }}">{{ $contentType->name }}</a></td>
                        <td>{{ $contentType->contents->count() }}</td>
                        <td>{{ $contentType->created_at ? $contentType->created_at->diffForHumans() : '-' }}</td>
                        <td>{{ $contentType->updated_at ? $contentType->updated_at->diffForHumans() : '-' }}</td>
                        <td>
                            <form action="{{ route('admin.menus.content-types.destroy', $contentType) }}"
                                method="POST"
                                onsubmit="javascript:return confirm('This item will be permanently deleted')">
                                @csrf
                                @method('DELETE')
                                <button title="Delete content-type '{{ $contentType->name }}'" class="btn">
                                    <i class="fas fa-trash fa-fw text-danger" aria-hidden="true"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <a href="{{ route('admin.menus.content-types.create') }}" class="btn btn-success">
            <i class="fas fa-plus-square fa-fw"></i> Add a new menu content-type
        </a>
    </div>
</div>
