<x-livewire-tables::table.cell>
    {{ Helper::user($row->user) }}
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell class="text-nowrap">
    {{ Carbon\Carbon::createFromTimestamp($row->timestamp)->toDayDateTimeString() }}
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
    <div class="text-muted">{{ Str::ucfirst($row->type) }}</div>
    {{ $row->target }}
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
    @if (method_exists($this, 'getTableRowUrl'))
        <a href="{{ $this->getTableRowUrl($row) }}">{{ Str::words($row->comment, 20) }}</a>
    @else
        {{ Str::words($row->comment, 20) }}
    @endif
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
    <form action="{{ route('admin.users.comments.destroy', $row) }}" method="POST"
        onsubmit="javascript:return confirm('This item will be permanently deleted')">
        @csrf
        @method('DELETE')
        <button title="Delete comment'" class="btn">
            <i class="fas fa-trash fa-fw text-danger" aria-hidden="true"></i>
        </button>
    </form>
</x-livewire-tables::table.cell>
