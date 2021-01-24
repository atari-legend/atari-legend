<h2 class="card-title fs-4">Menu conditions</h2>

<div class="card mb-3 bg-light">
    <div class="card-body">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Created</th>
                    <th>Updated</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($conditions as $condition)
                    <tr>
                        <td><a href="{{ route('admin.menus.conditions.edit', $condition) }}">{{ $condition->name }}</a></td>
                        <td>{{ $condition->created_at ? $condition->created_at->diffForHumans() : '-' }}</td>
                        <td>{{ $condition->updated_at ? $condition->updated_at->diffForHumans() : '-' }}</td>
                        <td>
                            <form action="{{ route('admin.menus.conditions.destroy', $condition) }}"
                                method="POST"
                                onsubmit="javascript:return confirm('This item will be permanently deleted')">
                                @csrf
                                @method('DELETE')
                                <button title="Delete condition '{{ $condition->name }}'" class="btn">
                                    <i class="fas fa-trash fa-fw text-danger" aria-hidden="true"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <a href="{{ route('admin.menus.conditions.create') }}" class="btn btn-success">
            <i class="fas fa-plus-square fa-fw"></i> Add a new menu condition
        </a>
    </div>
</div>
