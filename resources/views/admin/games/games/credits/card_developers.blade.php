<div class="card mb-3 bg-light">
    <div class="card-body">
        <h2 class="card-title fs-4">Developers</h2>

        <form class="mt-2 mb-4 row row-cols-lg-auto g-3 align-items-center g-3 align-items-center" action="{{ route('admin.games.game-developers.store', $game) }}" method="POST">
            @csrf
            <div class="col-12">
                <input class="autocomplete form-control @error('developer_name') is-invalid @enderror"
                    name="developer_name" id="developer_name" type="search"
                    data-autocomplete-endpoint="{{ route('ajax.companies') }}"
                    data-autocomplete-key="pub_dev_name" data-autocomplete-id="pub_dev_id"
                    data-autocomplete-companion="developer" value="{{ old('developer_name') }}"
                    placeholder="Type a company name..." autocomplete="off" required>
                <input type="hidden" name="developer" value="{{ old('developer') }}">

                @error('developer')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>
            <div class="col-12">
                <select class="form-select" name="role">
                    <option @if (old('role') === null) selected @endif value="">-</option>
                    @foreach ($developerRoles as $role)
                        <option value="{{ $role->id }}" @if (old('role') !== null && old('role') === $role->id) selected @endif>{{ $role->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-success w-100">Add developer</button>
            </div>
        </form>

        @if ($game->developers->isNotEmpty())
            <table class="table table-striped table-sm">
            <thead>
                <tr>
                    <th>Developer</th>
                    <th>Role</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($game->developers->sortBy('pivot.role.name') as $developer)
                    <tr>
                        <td>
                            <a class="d-inline-block" href="{{ route('admin.games.companies.edit', $developer) }}">
                                {{ $developer->pub_dev_name }}
                            </a>
                        </td>
                        <td>{{ $developer->pivot->role->name ?? '-' }}</td>
                        <td>
                            <form action="{{ route('admin.games.game-developers.destroy', ['game' => $game, 'developer' => $developer]) }}"
                                method="POST"
                                onsubmit="javascript:return confirm('This item will be permanently deleted')">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="role" value="{{ $developer->pivot->role->id ?? '' }}">
                                <button title="Delete developer '{{ $developer->pub_dev_name }}'" class="btn btn-sm">
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
