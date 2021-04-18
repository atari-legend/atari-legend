<div class="card mb-3 bg-light">
    <div class="card-body">
        <h2 class="card-title fs-4">
            @if (isset($crew))
                Edit <em>{{ $crew->crew_name }}</em>
            @else
                Add a new crew
            @endif
        </h2>

        <form action="{{ isset($crew) ? route('admin.menus.crews.update', $crew) : route('admin.menus.crews.store') }}" method="post">
            @csrf
            @if (isset($crew))
                @method('PUT')
            @endif

            <div class="mb-3">
                <label for="name" class="form-label">Name</label>
                <input type="text" class="form-control @error('name') is-invalid @enderror"
                    id="name" name="name" placeholder="e.g.: The Replicants" required
                    value="{{ old('name', $crew->crew_name ?? '') }}">

                @error('name')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="mb-3">
                <label for="history" class="form-label">History</label>
                <textarea class="form-control @error('history') is-invalid @enderror"
                    id="history" name="history" rows="3">{{ old('history', $crew->crew_history ?? '') }}</textarea>

                @error('history')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            @if (isset($crew) && $crew->menuSets->isNotEmpty())
                <div class="mb-3">
                    Menu sets:
                    @foreach ($crew->menuSets as $set)
                        <a href="{{ route('admin.menus.sets.edit', $set) }}">{{ $set->name }}</a>
                        @if (!$loop->last), @endif
                    @endforeach
                </div>
            @endif

            @if (isset($crew) && $crew->releases->isNotEmpty())
                <div class="mb-3">
                    Releases:
                    @foreach ($crew->releases as $release)
                        <a href="{{ route('games.releases.show', $release) }}">{{ $release->game->game_name }}</a>
                        @if (!$loop->last), @endif
                    @endforeach
                </div>
            @endif

            <button type="submit" class="btn btn-success">Save</button>
            <a href="{{ route('admin.menus.crews.index') }}" class="btn btn-link">Cancel</a>
        </form>
    </div>
</div>

