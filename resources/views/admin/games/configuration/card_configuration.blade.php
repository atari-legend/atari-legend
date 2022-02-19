<div class="card mb-3 bg-light">
    <div class="card-body">
        <h2 class="card-title fs-4">{{ $label }}</h2>

        <table class="table table-hover">
            <tbody>
                @foreach ($items as $item)
                    <tr>
                        <td>
                            <form action="{{ route('admin.games.configuration.update', ['type' => $type, 'id' => $item->id]) }}"
                                method="POST">
                                @csrf
                                @method('PUT')

                                <div class="input-group">
                                    <span class="input-group-text">
                                        @if ($hasDescription)
                                            Name and description
                                        @else
                                            Name
                                        @endif
                                    </span>
                                    <input type="text" id="name" name="name" required  class="form-control" value="{{ $item->name }}">
                                    @if ($hasDescription)
                                        <input type="text" id="description" name="description" class="form-control" value="{{ $item->description }}">
                                    @endif
                                    <button class="btn btn-outline-success" type="submit">Update</button>
                                </div>
                            </form>
                        </td>
                        <td class="text-center">
                            <form action="{{ route('admin.games.configuration.destroy', ['type' => $type, 'id' => $item->id]) }}"
                                method="POST"
                                onsubmit="javascript:return confirm('This item will be permanently deleted')">
                                @csrf
                                @method('DELETE')

                                <button title="Delete '{{ $item->name }}'" class="btn">
                                    <i class="fas fa-trash fa-fw text-danger" aria-hidden="true"></i>
                                </button>
                            </form>

                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
