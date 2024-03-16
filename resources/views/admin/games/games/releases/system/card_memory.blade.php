<div class="card bg-light mb-3">
    <div class="card-body">
        <div class="row">
            <div class="col">
                <h3>Memory</h3>

                <form
                    action="{{ route('admin.games.releases.system-memory.update', ['game' => $release->game, 'release' => $release]) }}"
                    method="POST">
                    @method('PUT')
                    @csrf

                    <div class="mb-3 row row-cols-2">
                        <div class="col">
                            <label for="minimum_memory" class="form-label">Minimum memory</label>
                            <select multiple class="form-select @error('minimum_memory') is-invalid @enderror"
                                style="height: 12rem;" id="minimum_memory" name="minimum_memory[]">
                                @foreach ($memories as $memory)
                                    <option value="{{ $memory->id }}"
                                        @if ($release->memoryMinimums?->contains($memory)) selected @endif>
                                        {{ $memory->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">CTRL+click to select multiple. Unselect all values to remove all
                                memories.</div>

                            @error('minimum_memory')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                        <div class="col">
                            <label for="incompatible_memory" class="form-label">Incompatible memory</label>
                            <select multiple class="form-select @error('incompatible_memory') is-invalid @enderror"
                                style="height: 12rem;" id="incompatible_memory" name="incompatible_memory[]">
                                @foreach ($memories as $memory)
                                    <option value="{{ $memory->id }}"
                                        @if ($release->memoryIncompatibles?->contains($memory)) selected @endif>
                                        {{ $memory->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">CTRL+click to select multiple. Unselect all values to remove all
                                memories.</div>

                            @error('incompatible_memory')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                    </div>

                    <button type="submit" class="btn btn-success">Save</button>

                </form>

            </div>
        </div>
    </div>
</div>
