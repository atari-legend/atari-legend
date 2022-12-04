<div class="card mb-3 bg-light">
    <div class="card-body">

        <h2 class="card-title fs-4">
            @if (isset($series))
                {{ $series->name }}
            @else
                Create new series
            @endif
        </h2>

        <form
            action="{{ isset($series) ? route('admin.games.series.update', $series) : route('admin.games.series.store') }}"
            method="post">
            @csrf
            @isset($series)
                @method('PUT')
            @endisset

            <div class="mb-3 row">
                <div class="col">
                    <label for="name" class="form-label">Name</label>
                    <input type="text" required class="form-control @error('name') is-invalid @enderror"
                        name="name" id="name" value="{{ old('name', $series->name ?? '') }}">

                    @error('name')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
            </div>


            <button type="submit" class="btn btn-success">Save</button>
            <a href="{{ route('admin.games.series.index') }}" class="btn btn-link">Cancel</a>
        </form>


    </div>
</div>
