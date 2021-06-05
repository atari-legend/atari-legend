<div class="card mb-3 bg-light">
    <div class="card-body">
        <h2 class="card-title fs-4">Game credits</h2>

        <form class="mt-2 mb-4 row row-cols-lg-auto g-3 align-items-center g-3 align-items-center" action="{{ route('admin.games.game-credits.store', $game) }}" method="POST">
            @csrf
            <div class="col-12">
                <input class="autocomplete form-control @error('individual') is-invalid @enderror"
                    name="individual_name" id="individual_name" type="search"
                    data-autocomplete-endpoint="{{ route('ajax.individuals') }}"
                    data-autocomplete-key="ind_name" data-autocomplete-id="ind_id"
                    data-autocomplete-companion="individual" value="{{ old('individual_name') }}"
                    placeholder="Type an individual name..." autocomplete="off" required>
                <input type="hidden" name="individual" value="{{ old('individual') }}">

                @error('individual')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>
            <div class="col-12">
                <select class="form-select" name="role">
                        <option @if (old('role') === null) selected @endif value="">-</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role->id }}" @if (old('role') !== null && old('role') === $role->id) selected @endif>{{ $role->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-success w-100">Add credit</button>
            </div>
        </form>

        @if ($game->individuals->isNotEmpty())
            <table class="table table-striped table-sm">
            <thead>
                <tr>
                    <th>Individual</th>
                    <th>Role</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($game->individuals->sortBy('pivot.role.name') as $individual)
                    <tr>
                        <td>
                            <a class="d-inline-block" href="{{ config('al.legacy.base_url').'/admin/individuals/individuals_edit.php?ind_id='.$individual->ind_id }}">
                                {{ $individual->ind_name }}
                            </a>
                            @if ($individual->aka_list->isNotEmpty())
                                <small class="text-muted">aka. {{ $individual->aka_list->join(', ') }}</small>
                            @endif
                        </td>
                        <td>{{ $individual->pivot->role->name ?? '-' }}</td>
                        <td>
                            <form action="{{ route('admin.games.game-credits.destroy', ['game' => $game, 'individual' => $individual]) }}"
                                method="POST"
                                onsubmit="javascript:return confirm('This item will be permanently deleted')">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="role" value="{{ $individual->pivot->role->id ?? '' }}">
                                <button title="Delete credit '{{ $individual->ind_name }}'" class="btn btn-sm">
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
                No credits for the game yet.
            </p>
        @endif

    </div>
</div>
