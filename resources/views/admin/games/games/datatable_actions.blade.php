<div class="d-flex">
    <a href="{{ route('games.show', $row) }}" title="View on main site" class="btn text-primary">
        <i class="fas fa-eye"></i>
    </a>

    <form action="{{ route('admin.games.games.destroy', $row) }}" method="POST"
        onsubmit="javascript:return confirm('This item will be permanently deleted')">
        @csrf
        @method('DELETE')
        <button title="Delete game '{{ $row->game_name }}'" class="btn">
            <i class="fas fa-trash fa-fw text-danger" aria-hidden="true"></i>
        </button>
    </form>
</div>
