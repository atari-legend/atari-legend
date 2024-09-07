<form action="{{ route('admin.games.submissions.destroy', $row) }}" method="POST"
    onsubmit="javascript:return confirm('This item will be permanently deleted')">
    @csrf
    @method('DELETE')
    <button title="Delete submission" class="btn">
        <i class="fas fa-trash fa-fw text-danger" aria-hidden="true"></i>
    </button>
</form>

