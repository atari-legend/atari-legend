<?php

namespace App\Http\Livewire\Admin;

use App\Models\Crew;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Columns\LinkColumn;

class CrewsTable extends DataTableComponent
{
    public function configure(): void
    {
        $this->setPrimaryKey('crew_id');
        $this->setDefaultSort('crew_name');
    }

    public function columns(): array
    {
        return [
            LinkColumn::make('Name', 'crew_name')
                ->title(fn($row) => $row->crew_name)
                ->location(fn($row) => route('admin.menus.crews.edit', $row))
                ->searchable(
                    fn($query, $term) => $query->where('crew_name', 'like', '%' . $term . '%')
                )
                ->sortable(fn(Builder $query, string $direction) => $query->orderBy('crew_name')),
            Column::make('Logo')
                ->label(
                    fn($row) => $row->crew_logo !== null && trim($row->crew_logo) !== ''
                        ? '<img style="max-height: 2rem; max-width: 5rem;" src="' . asset('storage/images/crew_logos/' . $row->crew_id . '.' . trim($row->crew_logo)) . '">'
                        : ''
                )
                ->html()
                ->sortable(fn(Builder $query, string $direction) => $query->orderBy('crew_logo', $direction)),
            Column::make('Genealogy')
                ->label(function ($row) {
                    $output = [];
                    if ($row->subCrews->isNotEmpty()) {
                        $output[] = $row->subCrews->count() . "sub-crews";
                    }
                    if (($row->parentCrews->isNotEmpty())) {
                        $output[] = '<span class="text-muted">Part of:</span> ' . $row->parentCrews->pluck('crew_name')->join(', ');
                    }
                    return collect($output)->join('<br>');
                })
                ->html(),
            Column::make('Individuals')
                ->label(
                    fn($row) => $row->individuals->isNotEmpty()
                        ? $row->individuals->count()
                        : '-'
                ),
            Column::make('Actions')
                ->label(
                    fn($row) => view('admin.menus.crews.datatable_actions')->with(['row' => $row])
                ),
        ];
    }

    public function builder(): Builder
    {
        return Crew::select();
    }
}
