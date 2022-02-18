<div class="card mb-3 bg-light">
    <div class="card-body">

        <h2 class="card-title fs-4">
            @if (isset($spotlight))
                Spotlight {{ $spotlight->spotlight_id }}
            @else
                Create spotlight
            @endif
        </h2>

        <form action="{{ isset($spotlight) ? route('admin.others.spotlights.update', $spotlight) : route('admin.others.spotlights.store') }}"
            method="post" enctype="multipart/form-data">
            @csrf
            @isset($spotlight)
                @method('PUT')
            @endisset

            <div class="row">
                <div class="col-12 col-md-6">
                    <div class="mb-3">
                        <label for="spotlight" class="form-label">Spotlight</label>
                        <textarea required class="form-control @error('spotlight') is-invalid @enderror"
                            rows="5" name="spotlight" id="spotlight">{{ old('spotlight', $spotlight->spotlight ?? '') }}</textarea>

                        @error('spotlight')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="link" class="form-label">Link</label>
                        <input type="url" required class="form-control @error('link') is-invalid @enderror"
                            name="link" id="link" value="{{ old('link', $spotlight->link ?? '') }}">

                        @error('link')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="text-center">
                        <img class="p-1 border border-dark shadow-sm" style="max-height: 7rem"
                            src="{{ isset($spotlight->screenshot) ? $spotlight->screenshot->getUrl('spotlight') ?? asset('images/image-placeholder.png') : asset('images/image-placeholder.png') }}" alt="Spotlight image">

                        @if (isset($spotlight?->screenshot))
                            <button class="btn btn-link" type="button"
                                onclick="if (confirm('The image will be deleted')) document.getElementById('delete-image').submit();">
                                <i class="fas fa-trash-alt text-danger"></i>
                            </button>
                        @endif
                    </div>

                    <div class="mb-3">
                        <label for="image" class="form-label">Image</label>
                        <input type="file" class="form-control @error('image') is-invalid @enderror" name="image">
                        @if (isset($spotlight) && $spotlight->screenshot)
                            <div class="form-text">Select a file to replace the current image.</div>
                        @endif

                        @error('image')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                </div>
            </div>

            <button type="submit" class="btn btn-success">Save</button>
            <a href="{{ route('admin.others.spotlights.index') }}" class="btn btn-link">Cancel</a>

        </form>

        @if (isset($spotlight))
            <form action="{{ route('admin.others.spotlights.image.destroy', $spotlight) }}" method="post" id="delete-image">
                @csrf
                @method('DELETE')
            </form>
        @endif

    </div>
</div>
