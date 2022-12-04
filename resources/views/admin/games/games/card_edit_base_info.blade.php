<div class="card mb-3 bg-light">
    <div class="card-body">
        <h2 class="card-title fs-4">Base info</h2>

        <form action="{{ route('admin.games.games.update.base-info', $game) }}" method="POST">
            @csrf

            <div class="mb-3 row row-cols-2">
                <div class="col">
                    <label for="name" class="form-label">Name</label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" required
                        name="name" id="name" value="{{ old('name', $game->game_name ?? '') }}">

                    @error('name')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
                <div class="col">
                    <label for="series" class="form-label">Series</label>
                    <select class="form-select @error('series') is-invalid @enderror" name="series">
                        <option value="">-</option>
                        @foreach ($series as $serie)
                            <option value="{{ $serie->id }}" @if ($game->series?->id === $serie->id) selected @endif>{{ $serie->name }}</option>
                        @endforeach
                    </select>

                    @error('series')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
            </div>

            <div class="mb-3 row row-cols-2">
                <div class="col">
                    <label for="genres" class="form-label">Genres</label>
                    <select class="form-select @error('genres') is-invalid @enderror" multiple size="6" name="genres[]">
                        <option value="">-</option>
                        @foreach ($genres as $genre)
                            <option value="{{ $genre->id }}" @if (isset($game) && $game->genres->contains($genre)) selected @endif>{{ $genre->name }}</option>
                        @endforeach
                    </select>
                    <div class="form-text">CTRL+click to select multiple</div>

                    @error('genres')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
                <div class="col">
                    <label for="languages" class="form-label">Programming languages</label>
                    <select class="form-select @error('languages') is-invalid @enderror" multiple size="6" name="languages[]">
                        <option value="">-</option>
                        @foreach ($programmingLanguages as $language)
                            <option value="{{ $language->id }}" @if (isset($game) && $game->programmingLanguages->contains($language)) selected @endif>{{ $language->name }}</option>
                        @endforeach
                    </select>
                    <div class="form-text">CTRL+click to select multiple</div>

                    @error('languages')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
            </div>

            <div class="mb-3 row row-cols-2">
                <div class="col">
                    <label for="engines" class="form-label">Game engines</label>
                    <select class="form-select @error('engines') is-invalid @enderror" multiple size="6" name="engines[]">
                        <option value="">-</option>
                        @foreach ($engines as $engine)
                            <option value="{{ $engine->id }}" @if (isset($game) && $game->engines->contains($engine)) selected @endif>{{ $engine->name }}</option>
                        @endforeach
                    </select>
                    <div class="form-text">CTRL+click to select multiple</div>

                    @error('engines')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
                <div class="col">
                    <label for="controls" class="form-label">Controls</label>
                    <select class="form-select @error('controls') is-invalid @enderror" multiple size="6" name="controls[]">
                        <option value="">-</option>
                        @foreach ($controls as $control)
                            <option value="{{ $control->id }}" @if (isset($game) && $game->controls->contains($control)) selected @endif>{{ $control->name }}</option>
                        @endforeach
                    </select>
                    <div class="form-text">CTRL+click to select multiple</div>

                    @error('controls')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
            </div>

            <div class="mb-3 row row-cols-2">
                <div class="col">
                    <div class="mb-3">
                        <label for="port" class="form-label">Port</label>
                        <select class="form-select @error('port') is-invalid @enderror" name="port">
                            <option value="">-</option>
                            @foreach ($ports as $port)
                                <option value="{{ $port->id }}" @if ($game->port?->id === $port->id) selected @endif>{{ $port->name }}</option>
                            @endforeach
                        </select>

                        @error('port')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="progress" class="form-label">Progress system</label>
                        <select class="form-select @error('progress') is-invalid @enderror" name="progress">
                            <option value="">-</option>
                            @foreach ($progressSystems as $system)
                                <option value="{{ $system->id }}" @if ($game->progressSystem?->id === $system->id) selected @endif>{{ $system->name }}</option>
                            @endforeach
                        </select>

                        @error('progress')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                </div>

                <div class="col">
                    <label for="sound" class="form-label">Sound hardware</label>
                    <select class="form-select @error('sound') is-invalid @enderror" multiple size="6" name="sound[]">
                        <option value="">-</option>
                        @foreach ($soundHardwares as $hardware)
                            <option value="{{ $hardware->id }}" @if (isset($game) && $game->soundHardwares->contains($hardware)) selected @endif>{{ $hardware->name }}</option>
                        @endforeach
                    </select>
                    <div class="form-text">CTRL+click to select multiple</div>

                    @error('sound')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
            </div>

            <button type="submit" class="btn btn-success">Save</button>
            <a href="{{ route('admin.games.games.index') }}" class="btn btn-link">Cancel</a>
        </form>

    </div>

</div>

