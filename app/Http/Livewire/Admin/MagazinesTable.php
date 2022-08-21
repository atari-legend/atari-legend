<?php

namespace App\Http\Livewire\Admin;

use App\Models\Magazine;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class MagazinesTable extends DataTableComponent
{
    public string $defaultSortColumn = 'name';
    public string $defaultSortDirection = 'asc';

    public function columns(): array
    {
        return [
            Column::make('Name', 'name')->sortable(),
            Column::make('Created', 'created_at')->sortable(),
            Column::make('Updated', 'updated_at')->sortable(),
            Column::make('Issues'),
            Column::blank(),
        ];
    }

    public function query(): Builder
    {
        return Magazine::query()
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
        return route('admin.magazines.magazines.edit', $row);
    }

    public function rowView(): string
    {
        return 'admin.magazines.magazines.list_row';
    }
}
