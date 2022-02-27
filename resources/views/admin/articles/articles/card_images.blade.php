<div class="card mb-3 bg-light">
    <div class="card-body">

        <h2 class="card-title fs-4">Images</h2>

        @if (!isset($article))
            <p class="text-muted">Please save the article before adding images</p>
        @else

            <form class="mb-3" action="{{ route('admin.articles.articles.image.store', $article) }}"
                method="post" enctype="multipart/form-data">
                @csrf

                <div class="mb-3">
                    <label for="image[]" class="form-label">Add an image</label>
                    <input type="file" class="form-control @error('image') is-invalid @enderror" multiple name="image[]">
                </div>
                <button class="btn btn-success type="submit">Add image(s)</button>
            </form>

            <hr>

            <form action="{{ route('admin.articles.articles.image.update', $article) }}" id="update-images" method="post">
                @csrf
                @method('PUT')
            </form>

            <table class="table table-hover table-borderless">
                <tbody>
                    @foreach ($article->screenshots as $screenshot)
                        <tr>
                            <td class="col-2">
                                <img class="w-100" src="{{ $screenshot->getUrl('article') }}">
                            </td>
                            <td>
                                <label for="description-{{ $screenshot->pivot->getKey() }}" class="form-label">Description</label>
                                <textarea class="form-control" id="{{ $screenshot->pivot->getKey() }}" form="update-images"
                                    name="description-{{ $screenshot->pivot->getKey() }}">{{ old('description-'.$screenshot->pivot->getKey(), $screenshot->pivot->comment?->comment_text) }}</textarea>
                            </td>
                            <td class="text-center">
                                <form action="{{ route('admin.articles.articles.image.destroy', ['article' => $article, 'image' => $screenshot]) }}"
                                    method="POST"
                                    onsubmit="javascript:return confirm('This item will be permanently deleted')">
                                    @csrf
                                    @method('DELETE')

                                    <button title="Delete image" class="btn" type="submit">
                                        <i class="fas fa-trash fa-fw text-danger" aria-hidden="true"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <button class="btn btn-success" type="submit" form="update-images">Save changes</button>
        @endif
    </div>
</div>
