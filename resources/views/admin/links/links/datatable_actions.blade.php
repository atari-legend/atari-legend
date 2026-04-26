<form action="{{ route('admin.links.links.destroy', $row) }}" method="POST"
    onsubmit="javascript:return confirm('This link will be permanently deleted')">
    @csrf
    @method('DELETE')
    <button title="Delete link" class="btn">
        <i class="fas fa-trash fa-fw text-danger" aria-hidden="true"></i>
    </button>
</form>
