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
<div class="card bg-light">
    <div class="card-body">
        <h2 class="card-title fs-4">Disk content</h2>
        <div class="row mb-2">
            <p>
                <a href=""><i class="fas fa-plus fa-fw me-1"></i>Add game release or extra</a>
                <span class="text-muted">
                    Use this option for a game that is present on the menu, or to add
                    an extra (doc, hint, trainer, …) for a game that is present on the menu.
                </span>
            </p>
            <p>
                <a href=""><i class="fas fa-plus fa-fw me-1"></i>Add standalone game doc or extra</a>
                <span class="text-muted">
                    Use this option to add a doc, hint, trainer or other extra for a game
                    that is <strong>not</strong> present on the menu (e.g. a standalone doc or
                    trainer for a game).
                </span>
            </p>
            <p>
                <a href=""><i class="fas fa-plus fa-fw me-1"></i>Add software</a>
                <span class="text-muted">
                    Use this option to add a non-game software (e.g. demo, utility, …).
                </span>
            </p>
        </div>

        <div class="row">
            @foreach ($disk->contents->sortBy('order') as $content)
                <div class="col-12 col-sm-6 col-md-3 col-xl-2 d-flex">
                    @include('admin.menus.disks.card_content')
                </div>
            @endforeach
        </div>
    </div>
</div>
