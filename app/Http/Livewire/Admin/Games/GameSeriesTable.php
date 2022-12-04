<?php

namespace App\Http\Livewire\Admin\Games;

use App\Models\GameSeries;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class GameSeriesTable extends DataTableComponent
{
    public string $defaultSortColumn = 'name';

    public function columns(): array
    {
        return [
            Column::make('Name', 'name')->sortable(),
            Column::make('Games'),
            Column::blank(),
        ];
    }

    public function query(): Builder
    {
        return GameSeries::query()
            ->when(
                $this->getFilter('search'),
                fn ($query, $term) => $query->where('name', 'like', '%' . $term . '%')
            );
    }

    public function filters(): array
    {
        return [];
    }

    public function getTableRowUrl($row): string
    {
        return route('admin.games.series.edit', $row);
    }

    public function rowView(): string
    {
        return 'admin.games.series.list_row';
    }
}
