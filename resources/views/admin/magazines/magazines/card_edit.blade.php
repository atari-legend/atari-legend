<div class="card mb-3 bg-light">
    <div class="card-body">

        <h2 class="card-title fs-4">
            @if (isset($magazine))
                {{ $magazine->name }}
            @else
                Create magazine
            @endif
        </h2>

        <form action="{{ isset($magazine) ? route('admin.magazines.magazines.update', $magazine) : route('admin.magazines.magazines.store') }}"
            method="post" enctype="multipart/form-data">
            @csrf
            @isset($magazine)
                @method('PUT')
            @endisset

            <div class="row">
                <div class="col">
                    <div class="mb-3">
                        <label for="name" class="form-label">Name</label>
                        <input type="text" required class="form-control @error('name') is-invalid @enderror"
                            name="name" id="name" value="{{ old('name', $magazine->name ?? '') }}">

                        @error('name')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-success">Save</button>
            <a href="{{ route('admin.magazines.magazines.index') }}" class="btn btn-link">Cancel</a>

        </form>

    </div>
</div>
