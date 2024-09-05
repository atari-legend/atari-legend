
<form action="{{ route('admin.articles.articles.destroy', $row) }}" method="POST"
    onsubmit="javascript:return confirm('This item will be permanently deleted')">
    @csrf
    @method('DELETE')
    <button title="Delete article '{{ $row->article_title }}'" class="btn">
        <i class="fas fa-trash fa-fw text-danger" aria-hidden="true"></i>
    </button>
</form>
