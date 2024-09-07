<?php

namespace App\Http\Livewire\Admin\Games;

use App\Models\GameSeries;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Columns\LinkColumn;

class GameSeriesTable extends DataTableComponent
{
    public function configure(): void
    {
        $this->setPrimaryKey('id');
        $this->setDefaultSort('name');
    }

    public function columns(): array
    {
        return [
            LinkColumn::make('Name')
                ->title(fn($row) => $row->name)
                ->location(fn($row) => route('admin.games.series.edit', $row))
                ->searchable(
                    fn(Builder $query, string $term) => $query->where('name', 'like', '%' . $term . '%')
                )
                ->sortable(
                    fn(Builder $query, string $direction) => $query->orderBy('name', $direction)
                ),
            Column::make('Games')
                ->label(fn($row) => $row->games->count()),
            Column::make('Actions')
                ->label(
                    fn($row) => view('admin.games.series.datatable_actions')->with(['row' => $row])
                ),

        ];
    }

    public function builder(): Builder
    {
        return GameSeries::select();
    }
}
