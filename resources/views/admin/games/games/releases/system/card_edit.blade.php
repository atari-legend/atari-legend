<div class="card bg-light mb-3">
    <div class="card-body">
        <div class="row">
            <div class="col">
                <h3>System Info</h3>

                <form action="{{ route('admin.games.releases.system.update', ['game' => $release->game, 'release' => $release]) }}" method="POST">
                    @csrf

                    <div class="mb-3 row row-cols-3">
                        <div class="col">
                            <label for="resolutions" class="form-label">Screen resolutions</label>
                            <select multiple class="form-select @error('resolutions') is-invalid @enderror" style="height: 10rem;" id="resolutions" name="resolutions[]">
                                @foreach ($resolutions as $resolution)
                                <option value="{{ $resolution->id }}" @if ($release->resolutions?->contains($resolution)) selected @endif>
                                    {{ $resolution->name }}
                                </option>
                                @endforeach
                            </select>
                            <div class="form-text">CTRL+click to select multiple. Unselect all values to remove all trainers.</div>

                            @error('resolutions')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>
                        <div class="col">
                            <label for="systems" class="form-label">Incompatible systems</label>
                            <select multiple class="form-select @error('systems') is-invalid @enderror" style="height: 10rem;" id="systems" name="systems[]">
                                @foreach ($systems as $system)
                                <option value="{{ $system->id }}" @if ($release->systemIncompatibles?->contains($system)) selected @endif>
                                    {{ $system->name }}
                                </option>
                                @endforeach
                            </select>
                            <div class="form-text">CTRL+click to select multiple. Unselect all values to remove all trainers.</div>

                            @error('systems')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>

                        <div class="col">
                            <label for="emulators" class="form-label">Incompatible emulators</label>
                            <select multiple class="form-select @error('emulators') is-invalid @enderror" style="height: 10rem;" id="emulators" name="emulators[]">
                                @foreach ($emulators as $emulator)
                                <option value="{{ $emulator->id }}" @if ($release->emulatorIncompatibles?->contains($emulator)) selected @endif>
                                    {{ $emulator->name }}
                                </option>
                                @endforeach
                            </select>
                            <div class="form-text">CTRL+click to select multiple. Unselect all values to remove all trainers.</div>

                            @error('emulators')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <div class="col">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="true" id="hd" name="hd"
                                    @if ($release->hd_installable) checked @endif>
                                <label class="form-check-label" for="hd">
                                    Installable on hard-disk
                                </label>
                            </div>

                        </div>
                    </div>

                    <button type="submit" class="btn btn-success">Save</button>

                </form>

            </div>
        </div>
    </div>
</div>
