<div class="card bg-light mb-3">
    <div class="card-body">
        <div class="row">
            <div class="col">
                <h3>Scene</h3>

                <form action="{{ route('admin.games.releases.scene.update', ['game' => $release->game, 'release' => $release]) }}" method="POST">
                    @csrf

                    <div class="mb-3 row row-cols-3">
                        <label for="trainers" class="form-label">Trainer</label>
                        <select multiple class="form-select @error('trainers') is-invalid @enderror" style="height: 12rem;" id="trainers" name="trainers[]">
                            @foreach ($trainers as $trainer)
                            <option value="{{ $trainer->id }}" @if ($release->trainers?->contains($trainer)) selected @endif>
                                {{ $trainer->name }}
                            </option>
                            @endforeach
                        </select>
                        <div class="form-text">CTRL+click to select multiple. Unselect all values to remove all trainers.</div>

                        @error('trainer')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-success">Save</button>

                </form>

            </div>
        </div>
    </div>
</div>