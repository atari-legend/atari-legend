<h2 class="card-title fs-4">
    @if (isset($set))
        Edit <em>{{ $set->name }}</em>
    @else
        Add a new menu set
    @endif
</h2>

<div class="card mb-3 bg-light">
    <div class="card-body">
        <form action="{{ isset($set) ? route('admin.menus.sets.update', $set) : route('admin.menus.sets.store') }}" method="post">
            @csrf
            @if (isset($set))
                @method('PUT')
            @endif

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="name" class="form-label">Name</label>
                    <input type="text" class="form-control @error('email') is-invalid @enderror"
                        id="name" name="name" placeholder="e.g.: Automation" required
                        value="{{ old('name', $set->name ?? '') }}">

                    @error('name')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label for="sort" class="form-label">Menus sort direction</label>
                    <select class="form-select @error('sort') is-invalid @enderror"
                        id="sort" name="sort">
                        <option @if (old('sort', $set->menus_sort ?? 'ascending') == 'ascending') selected @endif value="asc">Ascending</option>
                        <option @if (old('sort', $set->menus_sort ?? 'ascending') == 'descending') selected @endif value="desc">Descending</option>
                    </select>

                    @error('sort')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
            </div>

            <div class="mb-3">
                <label for="crews" class="form-label">Crews</label>
                <select class="form-select @error('crews') is-invalid @enderror" multiple size="6" name="crews[]" required>
                    @foreach ($crews as $crew)
                        <option value="{{ $crew->crew_id }}" @if (isset($set) && $set->crews->contains($crew)) selected @endif>{{ $crew->crew_name }}</option>
                    @endforeach
                </select>

                @error('crews')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror

                <span class="form-text">Add & edit crews in the <a href="{{ config('al.legacy.base_url').'/admin/crew/crew_main.php' }}">Legacy CPANEL</a>.</span>
            </div>

            <button type="submit" class="btn btn-success">Save</button>
            <a href="{{ route('admin.menus.sets.index') }}" class="btn btn-link">Cancel</a>
        </form>
    </div>
</div>
