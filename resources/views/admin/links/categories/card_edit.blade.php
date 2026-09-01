<div class="card mb-3 bg-light">
    <div class="card-body">

        <h2 class="card-title fs-4">
            @if (isset($category))
                {{ $category->name }}
            @else
                Create category
            @endif
        </h2>

        @if ($errors->has('delete'))
            <div class="alert alert-danger">{{ $errors->first('delete') }}</div>
        @endif

        <form action="{{ isset($category) ? route('admin.links.categories.update', $category) : route('admin.links.categories.store') }}"
            method="post">
            @csrf
            @isset($category)
                @method('PUT')
            @endisset

            <div class="row">
                <div class="col-12 col-md-6">
                    <div class="mb-3">
                        <label for="name" class="form-label">Name</label>
                        <input type="text" required class="form-control @error('name') is-invalid @enderror"
                            name="name" id="name" value="{{ old('name', $category->name ?? '') }}">

                        @error('name')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-success">
                <i class="fas fa-save fa-fw"></i> Save
            </button>
            <a href="{{ route('admin.links.categories.index') }}" class="btn btn-link">Cancel</a>

        </form>
    </div>
</div>
