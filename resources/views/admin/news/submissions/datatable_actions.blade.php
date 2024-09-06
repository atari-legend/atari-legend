<form action="{{ route('admin.news.submissions.approve', $row) }}" method="POST"
    class="float-start">
    @csrf
    <button title="Approve submission '{{ $row->news_headline }}'" class="btn">
        <i class="fas fa-check fa-fw text-success" aria-hidden="true"></i>
    </button>
</form>

<form action="{{ route('admin.news.submissions.destroy', $row) }}" method="POST"
    onsubmit="javascript:return confirm('This item will be permanently deleted')">
    @csrf
    @method('DELETE')
    <button title="Delete submission '{{ $row->news_headline }}'" class="btn">
        <i class="fas fa-trash fa-fw text-danger" aria-hidden="true"></i>
    </button>
</form>
