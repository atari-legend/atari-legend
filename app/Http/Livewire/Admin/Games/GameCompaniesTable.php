<?php

namespace App\Http\Livewire\Admin\Games;

use App\Models\PublisherDeveloper;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Columns\LinkColumn;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

class GameCompaniesTable extends DataTableComponent
{
    public function configure(): void
    {
        $this->setPrimaryKey('pub_dev_id');
        $this->setDefaultSort('pub_dev_name');
    }

    public function columns(): array
    {
        return [
            LinkColumn::make('Name')
                ->title(fn($row) => $row->pub_dev_name)
                ->location(fn($row) => route('admin.games.companies.edit', $row))
                ->searchable(
                    fn(Builder $query, string $term) => $query->where('pub_dev_name', 'like', "%{$term}%")
                )
                ->sortable(
                    fn(Builder $query, string $direction) => $query->orderBy('pub_dev_name', $direction)
                ),
            Column::make('Logo')
                ->label(
                    fn($row) => $row->logo
                        ? '<img class="shadow-sm" style="max-height: 2rem" src="' . $row->logo . '" alt="Company logo">'
                        : ''
                )
                ->html(),
            Column::make('Actions')
                ->label(
                    fn($row) => view('admin.games.companies.datatable_actions')->with(['row' => $row])
                ),
        ];
    }

    public function builder(): Builder
    {
        return PublisherDeveloper::select();
    }

    public function filters(): array
    {
        return [
            'logo' => SelectFilter::make('Logo')
                ->options([
                    '' => '-',
                    'true' => 'Yes',
                    'false' => 'No'
                ])
                ->filter(
                    fn(Builder $query, string $term) => $term === 'true'
                        ? $query->whereHas('text', fn(Builder $subQuery) => $subQuery->whereNotNull('pub_dev_imgext'))
                        : $query->whereHas('text', fn(Builder $subQuery) => $subQuery->whereNull('pub_dev_imgext'))
                )
        ];
    }
}
