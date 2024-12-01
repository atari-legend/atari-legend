<div class="card mb-3 bg-light">
    <div class="card-body">
        <h2 class="card-title fs-4">Game facts</h2>

        <div class="mt-3">
            <a class="btn btn-success" href="{{ route('admin.games.game-facts.create', $game) }}">Add fact</a>
        </div>

        @if ($game->facts->isNotEmpty())
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Fact</th>
                        <th>Image</th>
                        <th colspan="2"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($game->facts as $fact)
                        <tr>
                            <td class="w-50">{!! Helper::bbCode(nl2br(e(Str::limit($fact->game_fact, 50)))) !!}</td>
                            <td>
                                @forelse ($fact->screenshots as $screenshot)
                                    <img class="w-100 mb-1" src="{{ $screenshot->getUrl('game_fact') }}">
                                @empty
                                    <span class="text-muted">-</span>
                                @endforelse
                            </td>
                            <td>
                                <a class="btn btn-link" href="{{ route('admin.games.game-facts.edit', ['game' => $game, 'fact' => $fact]) }}">
                                    <i class="fas fa-pen fa-fw" aria-hidden="true"></i>
                                </a>
                            </td>
                            <td>
                                <form action="{{ route('admin.games.game-facts.destroy', ['game' => $game, 'fact' => $fact]) }}"
                                    method="POST"
                                    onsubmit="javascript:return confirm('This item will be permanently deleted')">
                                    @csrf
                                    @method('DELETE')
                                    <button title="Delete fact" class="btn btn-sm">
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
                No facts for this game yet.
            </p>
        @endif

    </div>
</div>
