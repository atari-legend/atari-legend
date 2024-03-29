<x-livewire-tables::table.cell>
    @if (method_exists($this, 'getTableRowUrl'))
        <a href="{{ $this->getTableRowUrl($row) }}">{{ $row->display_label }}</a>
    @else
        {{ $row->display_label }}
    @endif
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
    {{ $row->published?->format("F Y") ?? '-' }}
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
    @if ($row->image_file)
        <img src="{{ $row->image }}" width="32" alt="Cover for {{ $row->display_label_with_date }}">
    @endif
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
    @if ($row->indices->isNotEmpty())
        <i class="fa-solid fa-check text-success"></i>
    @else
        <i class="fa-solid fa-times text-muted"></i>
    @endif
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
    {{ $row->created_at?->toDayDateTimeString() ?? '-' }}
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
    {{ $row->updated_at?->toDayDateTimeString() ?? '-' }}
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
