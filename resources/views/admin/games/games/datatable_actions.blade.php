<div class="d-flex">
    <a href="{{ route('games.show', $row) }}" title="View on main site" class="btn text-primary">
        <i class="fas fa-eye"></i>
    </a>

    <form action="{{ route('admin.games.games.destroy', $row) }}" method="POST"
        onsubmit="javascript:return confirm('This item will be permanently deleted')">
        @csrf
        @method('DELETE')
        @if ($row->is_deletable)
            <button type="submit" title="Delete game '{{ $row->name }}'" class="btn">
                <i class="fas fa-trash fa-fw text-danger" aria-hidden="true"></i>
            </button>
        @else
            {{--
                A game is only deletable while nothing references it. The title
                goes on the wrapper rather than the button because Bootstrap
                gives .btn:disabled pointer-events: none, so a title on the
                button itself never shows on hover. A plain title rather than a
                Bootstrap tooltip: that needs JavaScript, and this has to work
                on a page whose scripts did not arrive.

                The button is an affordance, not a boundary - GameController
                checks again before it deletes anything.
            --}}
            <span class="d-inline-block" title="Cannot be deleted: something still references this game">
                <button type="submit" class="btn" disabled
                    aria-label="Cannot delete game '{{ $row->name }}': something still references it">
                    <i class="fas fa-trash fa-fw text-danger" aria-hidden="true"></i>
                </button>
            </span>
        @endif
    </form>
</div>
