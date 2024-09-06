<?php

namespace App\Http\Livewire\Admin;

use App\Models\MenuSoftware;
use App\Models\MenuSoftwareContentType;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Columns\LinkColumn;
use Rappasoft\LaravelLivewireTables\Views\Filter;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

class SoftwareTable extends DataTableComponent
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
                ->location(fn($row) => route('admin.menus.software.edit', $row))
                ->searchable(fn(Builder $query, string $term) => $query->where('name', 'like', '%' . $term . '%'))
                ->sortable(fn(Builder $query, string $direction) => $query->orderBy('name', $direction)),
            Column::make('Type')
                ->label(fn($row) => $row->menuSoftwareContentType?->name ?? '-'),
            Column::make('Demozoo')
                ->label(
                    fn($row) => $row->demozoo_id
                        ? '<a href="https://demozoo.org/productions/' . $row->demozoo_id . '">
                            <img src="' . asset('images/demozoo-16x16.png') . '" alt="Demozoo link for {{ $row->name }}">
                        </a>'
                        : ''
                )
                ->html(),
            Column::make('Created', 'created_at')
                ->format(fn($value) => $value?->toDayDateTimeString() ?? '-')
                ->sortable(),
            Column::make('Updated', 'updated_at')
                ->format(fn($value) => $value?->toDayDateTimeString() ?? '-')
                ->sortable(),
            Column::make('Actions')
                ->label(
                    fn($row) => view('admin.menus.software.datatable_actions')->with(['row' => $row])
                ),
        ];
    }

    public function builder(): Builder
    {
        return MenuSoftware::select();
    }

    public function filters(): array
    {
        $types = MenuSoftwareContentType::all()
            // 0 is used to force the key being a string otherwise PHP
            // converts it into an int, and the filter doesn't work as
            // it's not using the key anymore but just an index
            // 'Games' get 0, 'Demo' get 1, etc.
            ->mapWithKeys(fn($type) => ['0' . $type->getKey() => $type->name])
            ->all();

        return [
            'type' => SelectFilter::make('Type')
                ->options(array_merge([
                    ''    => 'Any',
                ], $types))
                ->filter(fn(Builder $query, string $term) => $query->where('menu_software_content_type_id', '=', $term)),
        ];
    }
}
