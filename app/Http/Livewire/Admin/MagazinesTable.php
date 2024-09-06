<?php

namespace App\Http\Livewire\Admin;

use App\Models\Magazine;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Columns\LinkColumn;

class MagazinesTable extends DataTableComponent
{
    public function configure(): void
    {
        $this->setPrimaryKey('id');
        $this->setDefaultSort('name', 'asc');
    }

    public function columns(): array
    {
        return [
            LinkColumn::make('Name', 'name')
                ->title(
                    fn($row) => $row->name
                )
                ->location(
                    fn($row) => route('admin.magazines.magazines.edit', $row)
                )
                ->searchable(
                    fn(Builder $query, string $term) => fn($query, $term) => $query->where('name', 'like', '%' . $term . '%')
                )
                ->sortable(
                    fn(Builder $query, string $direction) => $query->orderBy('name', $direction)
                ),
            Column::make('Created', 'created_at')
                ->format(
                    fn($value) => $value?->toDayDateTimeString() ?? '-'
                )
                ->sortable(),
            Column::make('Updated', 'updated_at')
                ->format(
                    fn($value) => $value?->toDayDateTimeString() ?? '-'
                )
                ->sortable(),
            Column::make('Issues')
                ->label(fn($row) => $row->issues->count()),
            Column::make('Actions')
                ->label(
                    fn($row) => view('admin.magazines.magazines.datatable_actions')->with(['row' => $row])
                ),
        ];
    }

    public function builder(): Builder
    {
        return Magazine::select();
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
