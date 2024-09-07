<?php

namespace App\Http\Livewire\Admin\Games;

use App\Models\Game;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Columns\LinkColumn;

class GamesTable extends DataTableComponent
{
    public function configure(): void
    {
        $this->setPrimaryKey('game_id');
        $this->setDefaultSort('game_name');
    }

    public function columns(): array
    {
        return [
            LinkColumn::make('Name')
                ->title(fn ($row) => $row->game_name)
                ->location(fn ($row) => route('admin.games.games.edit', $row))
                ->searchable(
                    fn (Builder $query, string $term) => $query->where('game_name', 'like', '%' . $term . '%')
                )
                ->sortable(
                    fn (Builder $query, string $direction) => $query->orderBy('game_name', $direction)
                ),
            Column::make('Screenshot')
                ->label(
                    fn ($row) => $row->screenshots->isNotEmpty()
                        ? '<img style="max-height: 3rem; max-width: 6rem;" src="' . $row->screenshots->random()->getUrl('game') . '">'
                        : '-'
                )
                ->html(),
            Column::make('Releases')
                ->label(
                    fn ($row) => $row->non_menu_releases->count() . ' (Menus: ' . $row->menu_releases->count() . ')'
                ),
            Column::make('Developers')
                ->label(fn ($row) => $row->developers->pluck('pub_dev_name')->join(', ')),
            Column::make('Created', 'created_at')
                ->format(fn ($value) => $value?->toDayDateTimeString() ?? '-')
                ->sortable(),
            Column::make('Updated', 'updated_at')
                ->format(fn ($value) => $value?->toDayDateTimeString() ?? '-')
                ->sortable(),
            Column::make('Actions')
                ->label(
                    fn ($row) => view('admin.games.games.datatable_actions')->with(['row' => $row])
                ),
        ];
    }

    public function builder(): Builder
    {
        return Game::select();
    }
}
