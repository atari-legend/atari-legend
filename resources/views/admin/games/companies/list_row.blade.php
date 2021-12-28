<x-livewire-tables::table.cell>
    @if (method_exists($this, 'getTableRowUrl'))
        <a href="{{ $this->getTableRowUrl($row) }}">{{ $row->pub_dev_name }}</a>
    @else
        {{ $row->pub_dev_name }}
    @endif
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
    @if ($row->logo)
        <img class="shadow-sm" style="max-height: 2rem" src="{{ $row->logo }}" alt="Company logo">
    @endif
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
    <form action="{{ route('admin.games.companies.destroy', $row) }}" method="POST"
        onsubmit="javascript:return confirm('This item will be permanently deleted')">
        @csrf
        @method('DELETE')
        <button title="Delete company '{{ $row->pub_dev_name }}'" class="btn">
            <i class="fas fa-trash fa-fw text-danger" aria-hidden="true"></i>
        </button>
    </form>
</x-livewire-tables::table.cell>
