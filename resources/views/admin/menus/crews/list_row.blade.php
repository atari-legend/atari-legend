<x-livewire-tables::table.cell>
    @if (method_exists($this, 'getTableRowUrl'))
        <a href="{{ $this->getTableRowUrl($row) }}">{{ $row->crew_name }}</a>
    @else
        {{ $row->crew_name }}
    @endif
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
    {{ $row->parentCrew }}
    @if ($row->crew_logo !== null && trim($row->crew_logo) !== '')
        <img style="max-height: 2rem; max-width: 5rem;" src="{{ asset('storage/images/crew_logos/' . $row->crew_id . '.' . trim($row->crew_logo)) }}">
    @else
        <span class="text-muted">-</span>
    @endif
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
    @if ($row->subCrews->isNotEmpty())
        {{ $row->subCrews->count() }} sub-crews
    @endif
    @if ($row->parentCrews->isNotEmpty())
        Part of: {{ $row->parentCrews->pluck('crew_name')->join(', ') }}
    @endif
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
    @if ($row->individuals->isNotEmpty())
        {{ $row->individuals->count()}}
    @else
        <span class="text-muted">-</span>
    @endif
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
    <form action="{{ route('admin.menus.crews.destroy', $row) }}" method="POST"
        onsubmit="javascript:return confirm('This item will be permanently deleted')">
        @csrf
        @method('DELETE')
        <button
            @if ($row->menuSets->isNotEmpty())
                disabled
                title="Please unlink from all menu sets first"
            @else
                title="Delete crew '{{ $row->crew_name }}'"
            @endif
            class="btn">
            <i class="fas fa-trash fa-fw text-danger" aria-hidden="true"></i>
        </button>
    </form>
</x-livewire-tables::table.cell>
