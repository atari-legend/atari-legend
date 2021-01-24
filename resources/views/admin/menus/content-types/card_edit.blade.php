<h2 class="card-title fs-4">
    @if (isset($contentType))
        Edit <em>{{ $contentType->name }}</em>
    @else
        Add a new menu content-type
    @endif
</h2>

<div class="card mb-3 bg-light">
    <div class="card-body">
        <form action="{{ isset($contentType) ? route('admin.menus.content-types.update', $contentType) : route('admin.menus.content-types.store') }}" method="post">
            @csrf
            @if (isset($contentType))
                @method('PUT')
            @endif
            <div class="mb-3">
                <label for="name" class="form-label">Name</label>
                <input type="text" class="form-control @error('name') is-invalid @enderror"
                    id="name" name="name" placeholder="e.g.: Documentation" required
                    value="{{ old('name', $contentType->name ?? '') }}">

                    @error('name')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
            </div>
            <button type="submit" class="btn btn-success">Save</button>
            <a href="{{ route('admin.menus.content-types.index') }}" class="btn btn-link">Cancel</a>
        </form>
    </div>
</div>
