<div class="card mb-3 bg-light">
    <div class="card-body">
        <h2 class="card-title fs-4">Versus</h2>

        <form class="mb-4 row row-cols-lg-auto"
            action="{{ route('admin.games.games.vs.store', $game) }}"
            method="POST">
            @csrf

            <div class="col">
                <label class="form-label" for="amiga_id">LemonAmiga ID</label>
                <input class="form-control @error('amiga_id') is-invalid @enderror"
                    placeholder="e.g. '123'"
                    name="amiga_id" id="amiga_id" type="number" required>

                @error('amiga_id')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>
            <div class="col">
                <label class="form-label" for="lemon64_slug">Lemon64 URL Slug</label>
                <input class="form-control @error('lemon64_slug') is-invalid @enderror"
                    placeholder="e.g. 'speedball-2'"
                    name="lemon64_slug" id="lemon64_slug" type="text">

                @error('lemon64_slug')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>
            <div class="col-12">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-success w-100" id="add">Add Versus</button>
            </div>
        </form>

        @if ($game->vs->isNotEmpty())
            <table class="table table-striped table-sm">
            <thead>
                <tr>
                    <th>LemonAmiga ID</th>
                    <th>Lemon64 URL Slug</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($game->vs as $vs)
                    <tr>
                        <td>@if ($vs->amiga_id) <a href="{{ $vs->lemon_amiga_url }}">{{ $vs->amiga_id }}</a>  @else - @endif</td>
                        <td>@if ($vs->lemon64_slug) <a href="{{ $vs->lemon_64_url }}">{{ $vs->lemon64_slug }}</a>  @else - @endif</td>
                        <td>
                            <form action="{{ route('admin.games.games.destroy.vs', ['game' => $game, 'vs' => $vs]) }}"
                                method="POST"
                                onsubmit="javascript:return confirm('This item will be permanently deleted')">
                                @csrf
                                @method('DELETE')
                                <button title="Delete Versus" class="btn btn-sm">
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
                No Versus for this game yet.
            </p>
        @endif

    </div>

</div>

