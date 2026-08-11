@if ($row->games->isEmpty())
    <form action="{{ route('admin.games.series.destroy', $row) }}" method="POST"
        onsubmit="javascript:return confirm('This item will be permanently deleted')">
        @csrf
        @method('DELETE')
        <button title="Delete series '{{ $row->name }}'" class="btn">
            <i class="fas fa-trash fa-fw text-danger" aria-hidden="true"></i>
        </button>
    </form>
@else
    <button class="btn" disabled
        title="'{{ $row->name }}' still has {{ $row->games->count() }} game(s). Remove them first.">
        <i class="fas fa-trash fa-fw text-muted" aria-hidden="true"></i>
    </button>
@endif
