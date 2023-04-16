<x-livewire-tables::table.cell>
    @if (method_exists($this, 'getTableRowUrl'))
        <a href="{{ $this->getTableRowUrl($row) }}">{{ $row->name }}</a>
    @else
        {{ $row->name }}
    @endif
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
    {{ $row->menuSoftwareContentType->name ?? '-' }}
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
    @if ($row->demozoo_id)
        <a href="https://demozoo.org/productions/{{ $row->demozoo_id }}">
            <img src="{{ asset('images/demozoo-16x16.png') }}" alt="Demozoo link for {{ $row->name }}">
        </a>
    @else
        -
    @endif
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
    {{ $row->created_at?->diffForHumans() ?? '-' }}
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
    {{ $row->updated_at?->diffForHumans() ?? '-' }}
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
    <form action="{{ route('admin.menus.software.destroy', $row) }}" method="POST"
        onsubmit="javascript:return confirm('This item will be permanently deleted')">
        @csrf
        @method('DELETE')
        <button
            @if ($row->menuDiskContents->isNotEmpty()) disabled
            title="Software is in use and cannot be deleted"
        @else
            title="Delete software '{{ $row->name }}'" @endif
            class="btn">
            <i class="fas fa-trash fa-fw text-danger" aria-hidden="true"></i>
        </button>
    </form>
</x-livewire-tables::table.cell>
