<x-livewire-tables::table.cell>
    @if (method_exists($this, 'getTableRowUrl'))
        <a href="{{ $this->getTableRowUrl($row) }}">{{ $row->news_headline }}</a>
    @else
        {{ $row->userid }}
    @endif
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell class="text-nowrap">
    @if ($row->news_date)
        {{ $row->news_date->toDayDateTimeString() }}
    @else
        -
    @endif
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
    @if ($row->news_image)
        <img class="shadow-sm" style="max-height: 2rem"
            src="{{ $row->news_image }}"
            alt="News image">
    @endif
</x-livewire-tables::table.cell>


<x-livewire-tables::table.cell class="text-nowrap">
    @if ($row->user)
        {{ Helper::user($row->user)}}
    @else
        -
    @endif
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
    <form action="{{ route('admin.news.news.destroy', $row) }}" method="POST"
        onsubmit="javascript:return confirm('This item will be permanently deleted')">
        @csrf
        @method('DELETE')
        <button title="Delete news '{{ $row->news_headline }}'" class="btn">
            <i class="fas fa-trash fa-fw text-danger" aria-hidden="true"></i>
        </button>
    </form>
</x-livewire-tables::table.cell>
