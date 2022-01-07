<?php

namespace App\Http\Livewire\Admin\Games;

use App\Models\PublisherDeveloper;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class GameCompaniesTable extends DataTableComponent
{
    public string $primaryKey = 'pub_dev_id';

    public function columns(): array
    {
        return [
            Column::make('Name', 'ind_name')->sortable(),
            Column::make('Logo')
                ->sortable(function (Builder $query, $direction) {
                    return $query->orderBy('pub_dev_text.pub_dev_imgext', $direction);
                }),
            Column::blank(),
        ];
    }

    public function query(): Builder
    {
        return PublisherDeveloper::select('pub_dev.*')
            ->leftJoin('pub_dev_text', 'pub_dev_text.pub_dev_id', '=', 'pub_dev.pub_dev_id')
            ->when(
                $this->getFilter('search'),
                fn ($query, $term) => $query->where('pub_dev_name', 'like', "%{$term}%")
            );
    }

    public function filters(): array
    {
        return [];
    }

    public function getTableRowUrl($row): string
    {
        return route('admin.games.companies.edit', $row);
    }

    public function rowView(): string
    {
        return 'admin.games.companies.list_row';
    }
}
