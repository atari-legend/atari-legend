<?php

namespace App\Http\Livewire\Admin;

use App\Models\MagazineIssue;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Columns\LinkColumn;

class MagazineIssuesTable extends DataTableComponent
{
    public $magazine = 0;

    public function configure(): void
    {
        $this->setPrimaryKey('id');
        $this->setDefaultSort('issue', 'asc');
        $this->setSearchDisabled();
        $this->setPerPageAccepted([25, 50, 100, -1]);
    }

    public function columns(): array
    {
        return [
            LinkColumn::make('Issue', 'issue')
                ->title(fn ($row) => $row->display_label)
                ->location(
                    fn ($row) => route('admin.magazines.issues.edit', [
                        'magazine' => $row->magazine,
                        'issue'    => $row,
                    ])
                ),
            Column::make('Date', 'published')
                ->format(fn ($value) => $value?->format('F Y') ?? '-')
                ->sortable(),
            Column::make('Cover')
                ->label(
                    fn ($row) => $row->image_file
                        ? '<img src="' . $row->image . '" width="32" alt="Cover for ' . $row->display_label_with_date . '">'
                        : ''
                )
                ->html(),
            Column::make('Index')
                ->label(
                    fn ($row) => $row->indices->isNotEmpty()
                        ? '<i class="fa-solid fa-check text-success"></i>'
                        : '<i class="fa-solid fa-times text-muted"></i>'
                )
                ->html(),
            Column::make('Created', 'created_at')
                ->format(fn ($value) => $value?->toDayDateTimeString() ?? '-')
                ->sortable(),
            Column::make('Updated', 'updated_at')
                ->format(fn ($value) => $value?->toDayDateTimeString() ?? '-')
                ->sortable(),
            Column::make('Actions')
                ->label(
                    fn ($row) => view('admin.magazines.issues.datatable_actions')->with(['row' => $row])
                ),

        ];
    }

    public function builder(): Builder
    {
        return MagazineIssue::where('magazine_id', '=', $this->magazine)
            ->select();
    }
}
