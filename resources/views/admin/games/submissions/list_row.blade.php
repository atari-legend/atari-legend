<x-livewire-tables::table.cell>
    @if (method_exists($this, 'getTableRowUrl'))
        <a href="{{ $this->getTableRowUrl($row) }}">{{ $row->game?->game_name }}</a>
    @else
        {{ $row->game?->game_name }}
    @endif
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
    {{ $row->user?->userid }}
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell class="text-nowrap">
    @if ($row->timestamp)
        {{ Carbon\Carbon::createFromTimestamp($row->timestamp)->toDayDateTimeString() }}
    @else
        -
    @endif
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell class="text-nowrap">
    @if ($row->game_done === App\Models\GameSubmitInfo::SUBMISSION_REVIEWED)
        Yes
    @else
        No
    @endif
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
    <form action="{{ route('admin.games.submissions.destroy', $row) }}" method="POST"
        onsubmit="javascript:return confirm('This item will be permanently deleted')">
        @csrf
        @method('DELETE')
        <button title="Delete submission" class="btn">
            <i class="fas fa-trash fa-fw text-danger" aria-hidden="true"></i>
        </button>
    </form>
</x-livewire-tables::table.cell>
