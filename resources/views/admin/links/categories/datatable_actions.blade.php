<form action="{{ route('admin.links.categories.destroy', $row) }}" method="POST"
    onsubmit="javascript:return confirm('This category will be permanently deleted and removed from assigned links')">
    @csrf
    @method('DELETE')
    <button title="Delete category" class="btn">
        <i class="fas fa-trash fa-fw text-danger" aria-hidden="true"></i>
    </button>
</form>
