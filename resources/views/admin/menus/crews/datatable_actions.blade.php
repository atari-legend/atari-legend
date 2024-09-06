<form action="{{ route('admin.menus.crews.destroy', $row) }}" method="POST"
    onsubmit="javascript:return confirm('This item will be permanently deleted')">
    @csrf
    @method('DELETE')
    <button
        @if ($row->menuSets->isNotEmpty())
            disabled
            title="Please unlink from all menu sets first"
        @else
            title="Delete crew '{{ $row->crew_name }}'"
        @endif
        class="btn">
        <i class="fas fa-trash fa-fw text-danger" aria-hidden="true"></i>
    </button>
</form>
