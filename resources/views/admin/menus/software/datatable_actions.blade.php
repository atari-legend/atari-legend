<form action="{{ route('admin.menus.software.destroy', $row) }}" method="POST"
    onsubmit="javascript:return confirm('This item will be permanently deleted')">
    @csrf
    @method('DELETE')
    <button
        @if ($row->menuDiskContents->isNotEmpty()) disabled
        title="Software is in use and cannot be deleted"
    @else
        title="Delete software '{{ $row->name }}'" @endif
        class="btn">
        <i class="fas fa-trash fa-fw text-danger" aria-hidden="true"></i>
    </button>
</form>
