<div class="card mb-3 bg-light">
    <div class="card-body">
        <h2 class="card-title fs-4">
            @if (isset($content))
                Edit content <em>{{ $content->label }}</em> @if ($disk->label !== '') of disk: {{ $disk->label }} @endif
            @else
                Add a new content in disk {{ $disk->label }}
            @endif
        </h2>

        <form action="{{ isset($content) ? route('admin.menus.disks.content.update', ['disk' => $disk, 'content' => $content]) : route('admin.menus.disks.content.store', ['disk' => $disk]) }}" method="post">
            @csrf
            @if (isset($content))
                @method('PUT')
            @endif
            @if (isset($type))
                <input type="hidden" name="type" value="{{ $type }}">
            @endif

            @isset ($content)
                @if ($content->game)
                    <p>
                        This content points to the game <a href="{{ config('al.legacy.base_url').'/admin/games/games_detail.php?game_id='.$content->game->game_id }}">{{ $content->game->game_name }}</a>.
                        It has no release, meaning the game itself is not present on the menu. It is either a documentation, trainer, hint, … so the
                        <strong>subtype field must be provided</strong>.
                    </p>
                @elseif ($content->release)
                    <p>
                        This content points to a specific <a href="{{ config('al.legacy.base_url').'/admin/games/games_release_detail.php?release_id='.$content->release->id.'&game_id='.$content->release->game->game_is }}">release</a>
                        of <a href="{{ config('al.legacy.base_url').'/admin/games/games_detail.php?game_id='.$content->release->game->game_id }}">{{ $content->release->game->game_name }}</a>.
                        It means the game itself is present on the menu, or it is a documentation / trainer / hint for a game present on another disk of the menu.
                    </p>
                @elseif ($content->menuSoftware)
                    <p>
                        This content points to the software <a href="{{ route('admin.menus.software.edit', $content->menuSoftware) }}">{{ $content->menuSoftware->name }}</a>.
                    </p>
                @else
                    <p class="text-danger">
                        This content does not point to any game, release, or software! It is likely an error and should be deleted.
                    </p>
                @endif
            @else
                @include('admin.menus.disks.content.create_'.$type)
            @endif

            <div class="row mb-3">
                <div class="col-12 col-md-6">
                    <label for="order" class="form-label">Order</label>
                    <input type="number" class="form-control @error('order') is-invalid @enderror"
                        id="order" name="order" placeholder="e.g.: 1" required
                        value="{{ old('order', $content->order ?? '') }}">

                    @error('order')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <div class="col-12 col-md-6">
                    <label for="subtype" class="form-label">Subtype</label>
                    <input type="text" class="form-control @error('subtype') is-invalid @enderror"
                        id="subtype" name="subtype" placeholder="e.g.: doc, hints, trainer, …"
                        value="{{ old('subtype', $content->subtype ?? '') }}">

                    @error('subtype')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-12 col-md-6">
                    <label for="version" class="form-label">Version</label>
                    <input type="text" class="form-control @error('version') is-invalid @enderror"
                        id="version" name="version" placeholder="e.g.: 2.3"
                        value="{{ old('version', $content->version ?? '') }}">

                    @error('version')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <div class="col-12 col-md-6">
                    <label for="requirements" class="form-label">Requirements</label>
                    <input type="text" class="form-control @error('requirements') is-invalid @enderror"
                        id="requirements" name="requirements" placeholder="e.g.: TOS 1.62"
                        value="{{ old('requirements', $content->requirements ?? '') }}">

                    @error('requirements')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
            </div>

            <button type="submit" class="btn btn-success">Save</button>
            <a href="{{ route('admin.menus.disks.edit', $disk) }}" class="btn btn-link">Cancel</a>

        </form>


    </div>
</div>
