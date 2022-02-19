<div class="card mb-3 bg-light">
    <div class="card-body">
        <h2 class="card-title fs-4">Article Types</h2>

        <table class="table table-hover">
            <tbody>
                @foreach ($types as $type)
                    <tr>
                        <td>
                            <form action="{{ route('admin.articles.types.update', $type) }}"
                                method="POST">
                                @csrf
                                @method('PUT')

                                <div class="input-group">
                                    <input type="text" id="type" name="type" required  class="form-control" value="{{ $type->article_type }}">
                                    <button class="btn btn-outline-success" type="submit">Update</button>
                                </div>
                            </form>
                        </td>
                        <td class="text-center">
                            <form action="{{ route('admin.articles.types.destroy', $type) }}"
                                method="POST"
                                onsubmit="javascript:return confirm('This type will be permanently deleted')">
                                @csrf
                                @method('DELETE')

                                <button title="Delete '{{ $type->article_type }}'" class="btn">
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
