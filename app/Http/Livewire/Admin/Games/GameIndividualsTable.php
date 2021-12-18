<?php

namespace App\Http\Livewire\Admin\Games;

use App\Models\Individual;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class GameIndividualsTable extends DataTableComponent
{
    public string $primaryKey = 'ind_id';

    public function columns(): array
    {
        return [
            Column::make('Name', 'ind_name')->sortable(),
            Column::make('Nicks or main name'),
            Column::make('Avatar')
                ->sortable(function (Builder $query, $direction) {
                    return $query->orderBy('individual_text.ind_imgext', $direction);
                }),
            Column::make('Games'),
            Column::blank(),
        ];
    }

    public function query(): Builder
    {
        return Individual::select('individuals.*')
            ->leftJoin('individual_text', 'individuals.ind_id', '=', 'individual_text.ind_id')
            ->when(
                $this->getFilter('search'),
                fn ($query, $term) => $query->where('ind_name', 'like', "%{$term}%")
                    // Also search nicks
                    ->orWhereHas('nicknames', function (Builder $query) use ($term) {
                        return $query->where('ind_name', 'like', "%{$term}%");
                    })
            );
    }

    public function filters(): array
    {
        return [];
    }

    public function getTableRowUrl($row): string
    {
        return route('admin.games.individuals.edit', $row);
    }

    public function rowView(): string
    {
        return 'admin.games.individuals.list_row';
    }
}
