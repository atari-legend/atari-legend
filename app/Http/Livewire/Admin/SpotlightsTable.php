<?php

namespace App\Http\Livewire\Admin;

use App\Models\Spotlight;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class SpotlightsTable extends DataTableComponent
{
    public string $primaryKey = 'spotlight_id';

    public function columns(): array
    {
        return [
            Column::make('Spotlight', 'spotlight')->sortable(),
            Column::make('Link', 'link')->sortable(),
            Column::blank(),
        ];
    }

    public function query(): Builder
    {
        return Spotlight::query()
            ->when(
                $this->getFilter('search'),
                fn ($query, $term) => $query->where('spotlight', 'like', "%{$term}%")
                    ->orWhere('link', 'like', "%{$term}%")
            );
    }

    public function filters(): array
    {
        return [];
    }

    public function getTableRowUrl($row): string
    {
        return route('admin.others.spotlights.edit', $row);
    }

    public function rowView(): string
    {
        return 'admin.others.spotlights.list_row';
    }
}
