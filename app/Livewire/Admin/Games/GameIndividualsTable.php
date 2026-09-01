<?php

namespace App\Livewire\Admin\Games;

use App\Models\Individual;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Columns\LinkColumn;

class GameIndividualsTable extends DataTableComponent
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
                ->title(fn ($row) => $row->name)
                ->location(fn ($row) => route('admin.games.individuals.edit', $row))
                ->searchable(
                    fn (Builder $query, string $term) => $query->where('name', 'like', "%{$term}%")
                        // Also search nicks
                        ->orWhereHas('nicknames', function (Builder $query) use ($term) {
                            return $query->where('name', 'like', "%{$term}%");
                        })
                )
                ->sortable(fn (Builder $query, string $direction) => $query->orderBy('name', $direction)),
            Column::make('Nicks or main name')
                ->label(
                    fn ($row) => $row->nick_list->join(', ') . $row->individual_list->join(', ')
                ),
            Column::make('Avatar')
                ->sortable(function (Builder $query, $direction) {
                    return $query->orderBy('imgext', $direction);
                })
                ->label(
                    fn ($row) => $row->avatar
                        ? '<img class="shadow-sm" style="max-height: 2rem" src="' . $row->avatar . '" alt="Individual avatar">'
                        : ''
                )
                ->html(),
            Column::make('Games')
                ->label(
                    fn ($row) => $row->games->count()
                ),
            Column::make('Actions')
                ->label(
                    fn ($row) => view('admin.games.individuals.datatable_actions')->with(['row' => $row])
                ),

        ];
    }

    public function builder(): Builder
    {
        return Individual::query()->select('individuals.*');
    }
}
