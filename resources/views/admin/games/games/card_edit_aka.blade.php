<div class="card mb-3 bg-light">
    <div class="card-body">
        <h2 class="card-title fs-4">AKAs</h2>

        <form class="mb-4 row row-cols-lg-auto"
            action="{{ route('admin.games.games.aka.store', $game) }}"
            method="POST">
            @csrf

            <div class="col">
                <input class="form-control @error('aka') is-invalid @enderror"
                    name="aka" id="aka" type="text"required>

                @error('aka')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>
            <div class="col">
                <select class="form-select" name="language" id="language">
                    <option value="">-</option>
                    @foreach ($languages as $language)
                        <option value="{{ $language->id }}" @if (old('language') !== null && old('language') === $language->id) selected @endif>{{ $language->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-success w-100" id="add">Add AKA</button>
            </div>
        </form>

        @if ($game->akas->isNotEmpty())
            <table class="table table-striped table-sm">
            <thead>
                <tr>
                    <th>AKA</th>
                    <th>Language</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($game->akas->sortBy('aka_name') as $aka)
                    <tr>
                        <td>{{ $aka->aka_name }}</td>
                        <td>{{ $aka->language?->name ?? '-' }}</td>
                        <td>
                            <form action="{{ route('admin.games.games.destroy.aka', ['game' => $game, 'aka' => $aka]) }}"
                                method="POST"
                                onsubmit="javascript:return confirm('This item will be permanently deleted')">
                                @csrf
                                @method('DELETE')
                                <button title="Delete AKA '{{ $aka->aka_name }}'" class="btn btn-sm">
                                    <i class="fas fa-trash fa-fw text-danger" aria-hidden="true"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
            </table>
        @else
            <p class="card-text text-center text-muted">
                No AKA for this game yet.
            </p>
        @endif

    </div>

</div>

