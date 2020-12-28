<div class="card mb-3 bg-light">
    <div class="card-body">
        <h2 class="card-title fs-4">
            @if (isset($menu))
                Edit menu <em>{{ $menu->label }}</em> of set: {{ $set->name }}
            @else
                Add a new menu in set: {{ $set->name }}
            @endif
        </h2>
        <form action="{{ isset($menu) ? route('admin.menus.menus.update', $menu) : route('admin.menus.menus.store') }}" method="post">
            @csrf
            <input type="hidden" name="set" value="{{ $set->id }}">
            @if (isset($menu))
                @method('PUT')
            @endif

            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="number" class="form-label">Number</label>
                    <input type="number" class="form-control @error('number') is-invalid @enderror"
                        id="number" name="number" placeholder="e.g.: 1"
                        value="{{ old('number', $menu->number ?? '') }}">

                    @error('number')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
                <div class="col-md-4">
                    <label for="issue" class="form-label">Issue</label>
                    <input type="text" class="form-control @error('issue') is-invalid @enderror"
                        id="issue" name="issue" placeholder="e.g.: A"
                        value="{{ old('issue', $menu->issue ?? '') }}">

                    @error('issue')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
                <div class="col-md-4">
                    <label for="version" class="form-label">Version</label>
                    <input type="text" class="form-control @error('version') is-invalid @enderror"
                        id="version" name="version" placeholder="e.g.: 3"
                        value="{{ old('version', $menu->version ?? '') }}">

                    @error('version')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
            </div>


            <div class="mb-3">
                <label for="date" class="form-label">Release date</label>
                <input type="date" class="form-control @error('date') is-invalid @enderror"
                    id="date" name="date" placeholder="e.g.: 03.02.1989"
                    value="{{ old('date', (isset($menu) && $menu->date) ? $menu->date->toDateString() : '') }}">

                    @error('date')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
            </div>

            <div class="mb-3">
                <label for="notes" class="form-label">Notes</label>
                <textarea class="form-control @error('number') is-invalid @enderror"
                    id="notes" name="notes" rows="5">{{ old('notes', $menu->notes ?? '') }}</textarea>

                @error('notes')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <button type="submit" class="btn btn-success">Save</button>
            <a href="{{ route('admin.menus.sets.edit', $set) }}" class="btn btn-link">Cancel</a>
        </form>
    </div>
</div>
