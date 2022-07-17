<x-livewire-tables::table.cell>
    @if (method_exists($this, 'getTableRowUrl'))
        <a href="{{ $this->getTableRowUrl($row) }}">{{ $row->issue }}</a>
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
    <form action="{{ route('admin.magazines.issues.destroy', ['magazine' => $row->magazine, 'issue' => $row]) }}" method="POST"
        onsubmit="javascript:return confirm('This item will be permanently deleted')">
        @csrf
        @method('DELETE')
        <button title="Delete issue '{{ $row->number }}'" class="btn">
            <i class="fas fa-trash fa-fw text-danger" aria-hidden="true"></i>
        </button>
    </form>
</x-livewire-tables::table.cell>
