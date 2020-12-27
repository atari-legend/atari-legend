<div class="card mb-3 bg-light">
    <div class="card-body">
        <h2 class="card-title fs-4">
            @if (isset($condition))
                Edit <em>{{ $condition->name }}</em>
            @else
                Add a new menu condition
            @endif
        </h2>
        <form action="{{ isset($condition) ? route('admin.menus.conditions.update', $condition) : route('admin.menus.conditions.store') }}" method="post">
            @csrf
            @if (isset($condition))
                @method('PUT')
            @endif
            <div class="mb-3">
                <label for="name" class="form-label">Name</label>
                <input type="text" class="form-control @error('name') is-invalid @enderror"
                    id="name" name="name" placeholder="e.g.: Intact" required
                    value="{{ old('name', $condition->name ?? '') }}">

                    @error('name')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
            </div>
            <button type="submit" class="btn btn-success">Save</button>
            <a href="{{ route('admin.menus.conditions.index') }}" class="btn btn-link">Cancel</a>
        </form>
    </div>
</div>
