<div class="card mb-3 bg-light">
    <div class="card-body">

        <h2 class="card-title fs-4">Did you know?</h2>

        <table class="table table-hover">
            <tbody>
                @foreach ($trivias as $trivia)
                    <tr>
                        <td>
                            <form action="{{ route('admin.others.trivias.update', $trivia) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <div class="input-group">
                                    <textarea class="form-control" name="text" rows="3">{{ $trivia->trivia_text }}</textarea>
                                    <button class="btn btn-outline-success" type="submit">Update</button>
                                </div>
                            </form>
                        </td>
                        <td class="text-center">
                            <form action="{{ route('admin.others.trivias.destroy', $trivia) }}"
                                method="POST"
                                onsubmit="javascript:return confirm('This item will be permanently deleted')">
                                @csrf
                                @method('DELETE')

                                <button title="Delete trivia" class="btn">
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
