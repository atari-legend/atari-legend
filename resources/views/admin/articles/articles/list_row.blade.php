<x-livewire-tables::table.cell>
    @if (method_exists($this, 'getTableRowUrl'))
        <a href="{{ $this->getTableRowUrl($row) }}">{{ $row->texts->first()->article_title }}</a>
    @else
        {{ $row->article_title }}
    @endif
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell class="text-nowrap">
    @if ($row->texts?->first()?->article_date)
        {{ $row->texts->first()->article_date->toFormattedDateString() }}
    @else
        -
    @endif
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell class="text-nowrap">
    @if ($row->user)
        {{ Helper::user($row->user)}}
    @else
        -
    @endif
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell class="text-nowrap">
    @if ($row->draft)
        <span class="text-warning">Yes</span>
    @else
        <span class="text-success">No</span>
    @endif
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
    <form action="{{ route('admin.articles.articles.destroy', $row) }}" method="POST"
        onsubmit="javascript:return confirm('This item will be permanently deleted')">
        @csrf
        @method('DELETE')
        <button title="Delete article '{{ $row->article_title }}'" class="btn">
            <i class="fas fa-trash fa-fw text-danger" aria-hidden="true"></i>
        </button>
    </form>
</x-livewire-tables::table.cell>
