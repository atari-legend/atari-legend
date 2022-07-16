<x-livewire-tables::table.cell>
    @if (method_exists($this, 'getTableRowUrl'))
        <a href="{{ $this->getTableRowUrl($row) }}">{{ $row->name }}</a>
    @else
        {{ $row->name }}
    @endif
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
    {{ $row->created_at?->toDayDateTimeString() ?? '-' }}
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
    {{ $row->created_at?->toDayDateTimeString() ?? '-' }}
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
    {{ $row->issues->count() }}
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
    @if ($row->issues->isNotEmpty())
        <button disabled title="Delete all issues first" class="btn">
            <i class="fas fa-trash fa-fw text-muted" aria-hidden="true"></i>
        </button>
    @else
        <form action="{{ route('admin.magazines.magazines.destroy', $row) }}" method="POST"
            onsubmit="javascript:return confirm('This item will be permanently deleted')">
            @csrf
            @method('DELETE')
            <button title="Delete magazine '{{ $row->name }}'" class="btn">
                <i class="fas fa-trash fa-fw text-danger" aria-hidden="true"></i>
            </button>
        </form>
    @endif
</x-livewire-tables::table.cell>
