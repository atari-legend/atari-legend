<?php

namespace App\Http\Livewire\Admin;

use App\Models\MenuSoftware;
use App\Models\MenuSoftwareContentType;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filter;

class SoftwareTable extends DataTableComponent
{
    public string $defaultSortColumn = 'name';

    public function columns(): array
    {
        return [
            Column::make('Name', 'name')->sortable(),
            Column::make('Type'),
            Column::make('Demozoo'),
            Column::make('Created', 'created_at')->sortable(),
            Column::make('Updated', 'updated_at')->sortable(),
            Column::blank(),
        ];
    }

    public function query(): Builder
    {
        return MenuSoftware::query()
            ->when(
                $this->getFilter('search'),
                fn ($query, $term) => $query->where('name', 'like', '%' . $term . '%')
            )
            ->when(
                $this->getFilter('type'),
                function ($query, $term) {
                    $query->where('menu_software_content_type_id', '=', $term);
                }
            );
    }

    public function filters(): array
    {
        $types = MenuSoftwareContentType::all()
            // 0 is used to force the key being a string otherwise PHP
            // converts it into an int, and the filter doesn't work as
            // it's not using the key anymore but just an index
            // 'Games' get 0, 'Demo' get 1, etc.
            ->mapWithKeys(fn ($type) => ['0' . $type->getKey() => $type->name])
            ->all();

        return [
            'type' => Filter::make('Type')
                ->select(array_merge([
                    ''    => 'Any',
                ], $types)),
        ];
    }

    public function getTableRowUrl($row): string
    {
        return route('admin.menus.software.edit', $row);
    }

    public function rowView(): string
    {
        return 'admin.menus.software.list_row';
    }
}
