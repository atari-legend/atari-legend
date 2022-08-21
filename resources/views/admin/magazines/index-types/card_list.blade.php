<div class="card mb-3 bg-light">
    <div class="card-body">
        <h2 class="card-title fs-4">Index types</h2>

        <table class="table table-hover">
            <thead>
                <tr>
                    <th></th>
                    <th></th>
                    <th>Used in</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($types->sortBy('name') as $type)
                    <tr>
                        <td>
                            <form action="{{ route('admin.magazines.index-types.update', $type) }}" method="POST">
                                @csrf
                                @method('PUT')

                                <div class="input-group">
                                    <input type="text" name="name" required class="form-control"
                                        value="{{ $type->name }}">
                                    <button class="btn btn-outline-success" type="submit">Update</button>
                                </div>

                            </form>
                        </td>
                        <td class="text-center">
                            <form action="{{ route('admin.magazines.index-types.destroy', $type) }}" method="POST"
                                onsubmit="javascript:return confirm('This item will be permanently deleted')">
                                @csrf
                                @method('DELETE')

                                <button title="Delete '{{ $type->name }}'" class="btn">
                                    <i class="fas fa-trash fa-fw text-danger" aria-hidden="true"></i>
                                </button>
                            </form>
                        </td>
                        <td>
                            {{ $type->magazineIndices->count() }} {{ Str::plural('index', $type->magazineIndices->count()) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
