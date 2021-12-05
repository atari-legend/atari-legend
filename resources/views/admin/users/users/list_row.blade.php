<x-livewire-tables::table.cell>
    @if (method_exists($this, 'getTableRowUrl'))
        <a href="{{ $this->getTableRowUrl($row) }}">{{ $row->userid }}</a>
    @else
        {{ $row->userid }}
    @endif
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
    @if ($row->avatar_ext)
        <img class="shadow-sm" style="max-height: 2rem"
            src="{{ asset('storage/images/user_avatars/' . $row->user_id . '.' . $row->avatar_ext) }}"
            alt="User avatar">
    @endif
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell class="text-nowrap">
    @if ($row->join_date)
        {{ Carbon\Carbon::createFromTimestamp($row->join_date)->toDayDateTimeString() }}
    @else
        -
    @endif
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell class="text-nowrap">
    @if ($row->last_visit)
        {{ Carbon\Carbon::createFromTimestamp($row->last_visit)->toDayDateTimeString() }}
    @else
        -
    @endif
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
    <form action="{{ route('admin.users.users.destroy', $row) }}" method="POST"
        onsubmit="javascript:return confirm('This item will be permanently deleted')">
        @csrf
        @method('DELETE')
        <button title="Delete user '{{ $row->userid }}'" class="btn">
            <i class="fas fa-trash fa-fw text-danger" aria-hidden="true"></i>
        </button>
    </form>
</x-livewire-tables::table.cell>
