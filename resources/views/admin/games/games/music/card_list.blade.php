<div class="card mb-3 bg-light">
    <div class="card-body">
        <h2 class="card-title fs-4">Music associated with the game</h2>

        @if ($game->sndhs->isNotEmpty())
            <table class="table table-striped table-sm mb-5">
            <thead>
                <tr>
                    <th>Path</th>
                    <th>Title</th>
                    <th>Composer</th>
                    <th>Ripper</th>
                    <th>Subtunes</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($game->sndhs as $sndh)
                    <tr>
                        <td>{{ $sndh->id }}</td>
                        <td>{{ $sndh->title }}</td>
                        <td>{{ $sndh->composer }}</td>
                        <td>{{ $sndh->ripper }}</td>
                        <td>{{ $sndh->subtunes }}</td>
                        <td>
                            <form action="{{ route('admin.games.game-music.destroy', ['game' => $game, 'sndh' => $sndh]) }}"
                                method="POST"
                                onsubmit="javascript:return confirm('This item will be permanently deleted')">
                                @csrf
                                @method('DELETE')
                                <button title="Delete song '{{ $sndh->id }}'" class="btn btn-sm">
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
                No music associated with the game yet.
            </p>
        @endif

    </div>
</div>
