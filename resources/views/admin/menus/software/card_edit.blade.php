<h2 class="card-title fs-4">
    @if (isset($software))
        Edit <em>{{ $software->name }}</em>
    @else
        Add a new menu software
    @endif
</h2>

<div class="card mb-3 bg-light">
    <div class="card-body">
        <form action="{{ isset($software) ? route('admin.menus.software.update', $software) : route('admin.menus.software.store') }}" method="post">
            @csrf
            @if (isset($software))
                @method('PUT')
            @endif
            <div class="mb-3">
                <label for="name" class="form-label">Name</label>
                <input type="text" class="form-control @error('name') is-invalid @enderror"
                    id="name" name="name" placeholder="e.g.: Demo Construction Kit" required
                    value="{{ old('name', $software->name ?? '') }}">

                @error('name')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="mb-3">
                <label for="type" class="form-label">Type</label>
                <select class="form-select @error('type') is-invalid @enderror"
                    id="type" name="type">
                    @foreach ($types as $type)
                        <option value="{{ $type->id }}"
                            @if (old('type', $software->menuSoftwareContentType->id ?? -1) == $type->id) selected @endif>
                            {{ $type->name }}
                        </option>
                    @endforeach
                </select>

                @error('type')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="mb-3">
                <label for="demozoo" class="form-label">Demozoo ID</label>
                <input type="number" class="form-control @error('demozoo') is-invalid @enderror"
                    id="demozoo" name="demozoo" placeholder="e.g.: 123456" required
                    value="{{ old('demozoo', $software->demozoo_id ?? '') }}">

                @error('demozoo')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            @if ($software->menuDiskContents->isNotEmpty())
                <div class="mb-3">
                    <label class="form-label">Present in</label>
                    <div>
                    @foreach ($software->menuDiskContents->pluck('menuDisk') as $disk)
                        <a href="{{ route('admin.menus.menus.edit', $disk->menu) }}">
                            {{ $disk->menu->menuSet->name }}
                            #{{ $disk->menu->label}}
                            {{ $disk->part}}
                            {{ $disk->label }}
                        </a>
                        @if (!$loop->last) , @endif
                    @endforeach
                    </div>
                </div>
            @endif

            <button type="submit" class="btn btn-success">Save</button>
            <a href="{{ route('admin.menus.software.index') }}" class="btn btn-link">Cancel</a>
        </form>
    </div>
</div>
