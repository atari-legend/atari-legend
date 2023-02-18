<div class="card mb-3 bg-light">
    <div class="card-body">
        <h2 class="card-title fs-4">Release {{ $release->full_label }}</h2>

        <form action="{{ route('admin.games.releases.update', ['game' => $release->game, 'release' => $release]) }}"
            method="POST">
            @csrf
            @method('PUT')

            <div class="mb-3 row row-cols-3">
                <div class="col">
                    <label for="name" class="form-label">Name</label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror"
                        name="name" id="name" value="{{ old('name', $release->name ?? '') }}">
                    <div class="form-text">
                        For example translated title if it is a country-specific release,
                        or a specific version number. Leave blank if the title is the same
                        as the game.
                    </div>

                    @error('name')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
                <div class="col">
                    <label for="year" class="form-label">Year</label>
                    <select class="form-select @error('year') is-invalid @enderror" name="year">
                        <option @if (old('year', $release->year) === null) selected @endif value="">Unknown</option>
                        @foreach (range('1984', date('Y')) as $year)
                            <option value="{{ $year }}" @if (old('year', $release->year) === $year) selected @endif>
                                {{ $year }}
                            </option>
                        @endforeach
                    </select>

                    @error('year')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
                <div class="col">
                    <label for="publisher" class="form-label">Publisher</label>
                    <select class="form-select" @error('publisher') is-invalid @enderror name="publisher">
                        <option @if (old('publisher', $release->publisher) === null) selected @endif value="">-</option>
                        @foreach ($publishers as $publisher)
                            <option value="{{ $publisher->getKey() }}"
                                @if (old('publisher', $release->publisher?->getKey()) === $publisher->getKey()) selected @endif>
                                {{ $publisher->pub_dev_name }}
                            </option>
                        @endforeach
                    </select>

                    @error('publisher')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
            </div>

            <div class="mb-3 row row-cols-3">
                <div class="col">
                    <label for="type" class="form-label">Type</label>
                    <select class="form-select @error('type') is-invalid @enderror" name="type">
                        <option @if (old('type', $release->type) === null) selected @endif value="">-</option>
                        @foreach ($types as $type)
                            <option value="{{ $type }}" @if (old('type', $release->type) === $type) selected @endif>
                                {{ $type }}
                            </option>
                        @endforeach
                    </select>

                    @error('type')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
                <div class="col">
                    <label for="license" class="form-label">License</label>
                    <select class="form-select @error('license') is-invalid @enderror" name="license">
                        <option @if (old('license', $release->license) === null) selected @endif value="">-</option>
                        @foreach ($licenses as $license)
                            <option value="{{ $license }}" @if (old('license', $release->license) === $license) selected @endif>
                                {{ $license }}
                            </option>
                        @endforeach
                    </select>

                    @error('license')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
                <div class="col">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" @error('status') is-invalid @enderror name="status">
                        <option @if (old('status', $release->status) === null) selected @endif value="">-</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status }}" @if (old('status', $release->status) === $status) selected @endif>
                                {{ $status }}
                            </option>
                        @endforeach
                    </select>

                    @error('status')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
            </div>

            <div class="mb-3 row row-cols-2">
                <div class="col">
                    <label for="locations" class="form-label">Location</label>
                    <select multiple class="form-select @error('locations') is-invalid @enderror"
                        style="height: 10rem;" id="locations" name="locations[]">
                        @foreach ($locations as $location)
                            <option value="{{ $location->id }}" @if ($release->locations?->contains($location)) selected @endif>
                                @if ($location->type == 'Country')
                                    &nbsp;&nbsp;&nbsp;&nbsp;
                                @endif
                                {{ $location->name }}
                            </option>
                        @endforeach
                    </select>
                    <div class="form-text">CTRL+click to select multiple. Unselect all values to remove all locations.</div>

                    @error('locations')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror

                </div>
                <div class="col">
                    <label for="notes" class="form-label">Notes</label>
                    <textarea class="form-control @error('notes') is-invalid @enderror" style="height: 10rem;"
                        placeholder="Internal note for CPANEL, will not be shown on the site"
                        name="notes" id="notes">{{ old('notes', $release->notes) }}</textarea>

                    @error('notes')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
            </div>

            <button type="submit" class="btn btn-success">Save</button>
            <a href="{{ route('admin.games.releases.index', $release->game) }}" class="btn btn-link">Cancel</a>

        </form>

    </div>
</div>
