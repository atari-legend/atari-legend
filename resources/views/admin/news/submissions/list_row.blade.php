<x-livewire-tables::table.cell>
    {{ $row->news_headline }}
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell class="text-nowrap">
    @if ($row->news_date)
        {{ $row->news_date->toDayDateTimeString() }}
    @else
        -
    @endif
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
    {!! Helper::bbCode(stripslashes(nl2br(e($row->news_text)))) !!}
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell class="text-nowrap">
    @if ($row->user)
        {{ Helper::user($row->user)}}
    @else
        -
    @endif
</x-livewire-tables::table.cell>

<x-livewire-tables::table.cell>
    <form action="{{ route('admin.news.submissions.approve', $row) }}" method="POST"
        class="float-start">
        @csrf
        <button title="Approve submission '{{ $row->news_headline }}'" class="btn">
            <i class="fas fa-check fa-fw text-success" aria-hidden="true"></i>
        </button>
    </form>

    <form action="{{ route('admin.news.submissions.destroy', $row) }}" method="POST"
        onsubmit="javascript:return confirm('This item will be permanently deleted')">
        @csrf
        @method('DELETE')
        <button title="Delete submission '{{ $row->news_headline }}'" class="btn">
            <i class="fas fa-trash fa-fw text-danger" aria-hidden="true"></i>
        </button>
    </form>
</x-livewire-tables::table.cell>
