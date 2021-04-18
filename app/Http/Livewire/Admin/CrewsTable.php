<?php

namespace App\Http\Livewire\Admin;

use App\Models\Crew;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class CrewsTable extends DataTableComponent
{
    public function columns(): array
    {
        return [
            Column::make('Name', 'crew_name')->sortable(),
            Column::make('Logo', 'crew_logo'),
            Column::make('Genealogy'),
            Column::make('Individuals'),
            Column::blank(),
        ];
    }

    public function query(): Builder
    {
        return Crew::query()
            ->when(
                $this->getFilter('search'),
                fn ($query, $term) => $query->where('crew_name', 'like', '%'.$term.'%')
            );
    }

    public function getTableRowUrl($row): string
    {
        return route('admin.menus.crews.edit', $row);
    }

    public function rowView(): string
    {
        return 'admin.menus.crews.list_row';
    }
}
