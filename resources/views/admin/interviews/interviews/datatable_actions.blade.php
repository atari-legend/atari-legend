
<form action="{{ route('admin.interviews.interviews.destroy', $row) }}" method="POST"
    onsubmit="javascript:return confirm('This item will be permanently deleted')">
    @csrf
    @method('DELETE')
    <button title="Delete interview '{{ $row->ind_name }}'" class="btn">
        <i class="fas fa-trash fa-fw text-danger" aria-hidden="true"></i>
    </button>
</form>
