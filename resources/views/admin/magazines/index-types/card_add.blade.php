<div class="card mb-3 bg-light">
    <div class="card-body">
        <h2 class="card-title fs-4">Add new type</h2>

        <form action="{{ route('admin.magazines.index-types.store') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label for="name" class="form-label">Name</label>
                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name"
                    name="name" required>

                @error('name')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>
            <button type="submit" class="btn btn-success">Add</button>
        </form>
    </div>
</div>
