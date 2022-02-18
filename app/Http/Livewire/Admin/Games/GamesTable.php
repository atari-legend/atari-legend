<?php

namespace App\Http\Livewire\Admin\Games;

use App\Models\Game;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class GamesTable extends DataTableComponent
{
    public string $primaryKey = 'game_id';

    public function columns(): array
    {
        return [
            Column::make('Name', 'game_name')->sortable(),
            Column::make('Screenshot'),
            Column::make('Releases'),
            Column::make('Developers'),
            Column::blank(),
        ];
    }

    public function query(): Builder
    {
        return Game::query()
            ->when(
                $this->getFilter('search'),
                fn ($query, $term) => $query->where('game_name', 'like', '%' . $term . '%')
            );
    }

    public function getTableRowUrl($row): string
    {
        return route('admin.games.games.edit', $row);
    }

    public function rowView(): string
    {
        return 'admin.games.games.list_row';
    }
}
