<x-livewire-tables::table.cell>
    @if (method_exists($this, 'getTableRowUrl'))
        <a href="{{ $this->getTableRowUrl($row) }}">{{ $row->ind_name }}</a>
    @else
        {{ $row->ind_name }}
    @endif
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
    {{ $row->nick_list->join(', ') }}
    {{ $row->individual_list->join(', ') }}
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
    @if ($row->avatar)
        <img class="shadow-sm" style="max-height: 2rem" src="{{ $row->avatar }}" alt="Individual avatar">
    @endif
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
    {{ $row->games->count() }}
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
    <form action="{{ route('admin.games.individuals.destroy', $row) }}" method="POST"
        onsubmit="javascript:return confirm('This item will be permanently deleted')">
        @csrf
        @method('DELETE')
        <button title="Delete individual '{{ $row->ind_name }}'" class="btn">
            <i class="fas fa-trash fa-fw text-danger" aria-hidden="true"></i>
        </button>
    </form>
</x-livewire-tables::table.cell>
