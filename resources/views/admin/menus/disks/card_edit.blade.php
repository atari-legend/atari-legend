<div class="card mb-3 bg-light">
    <div class="card-body">
        <h2 class="card-title fs-4">
            @if (isset($disk))
                Edit disk <em>{{ $disk->label }}</em> of menu: {{ $menu->label }}
            @else
                Add a new disk in menu: {{ $menu->label }}
            @endif
        </h2>
        <form action="{{ isset($disk) ? route('admin.menus.disks.update', $disk) : route('admin.menus.disks.store') }}" method="post">
            @csrf
            <input type="hidden" name="menu" value="{{ $menu->id }}">
            @if (isset($disk))
                @method('PUT')
            @endif

            <div class="mb-3">
                <label for="part" class="form-label">Part</label>
                <input type="text" class="form-control @error('part') is-invalid @enderror"
                    id="part" name="part" placeholder="e.g.: A"
                    value="{{ old('part', $disk->part ?? '') }}">

                @error('part')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="mb-3">
                <label for="notes" class="form-label">Notes</label>
                <textarea class="form-control @error('number') is-invalid @enderror"
                    id="notes" name="notes" rows="3">{{ old('notes', $disk->notes ?? '') }}</textarea>

                @error('notes')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="mb-3">
                <label for="scrolltext" class="form-label">Scrolltext</label>
                <textarea class="form-control @error('scrolltext') is-invalid @enderror"
                    id="scrolltext" name="scrolltext" rows="3">{{ old('scrolltext', $disk->scrolltext ?? '') }}</textarea>

                @error('scrolltext')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <button type="submit" class="btn btn-success">Save</button>
            <a href="{{ route('admin.menus.menus.edit', $menu) }}" class="btn btn-link">Cancel</a>
        </form>
    </div>
</div>
