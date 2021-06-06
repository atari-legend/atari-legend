<x-livewire-tables::table.cell>
    @if (method_exists($this, 'getTableRowUrl'))
        <a href="{{ $this->getTableRowUrl($row) }}">{{ $row->game_name }}</a>
    @else
        {{ $row->game_name }}
    @endif
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
    @if ($row->screenshots->isNotEmpty())
        <img style="max-height: 3rem; max-width: 6rem;" src="{{ $row->screenshots->random()->getUrl('game') }}">
    @else
        <span class="text-muted">-</span>
    @endif
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
    {{ $row->non_menu_releases->count() }}
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
    {{ $row->developers->pluck('pub_dev_name')->join(', ') }}
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
    <a href="{{ route('games.show', $row) }}" title="View on main site">
        <i class="fas fa-eye"></i>
    </a>
</x-livewire-tables::table.cell>
