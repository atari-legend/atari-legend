<?php

namespace App\Http\Livewire\Admin\Games;

use App\Models\GameSubmitInfo;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filter;

class GameSubmissionsTable extends DataTableComponent
{
    public string $primaryKey = 'game_submitinfo_id';

    public array $filters = [
        'processed' => 'no',
    ];

    public function columns(): array
    {
        return [
            Column::make('Game')
                ->sortable(function (Builder $query, $direction) {
                    return $query->join('game', 'game_submitinfo.game_id', '=', 'game.game_id')
                        ->orderBy('game.game_name', $direction);
                }),
            Column::make('User')
                ->sortable(function (Builder $query, $direction) {
                    return $query->join('users', 'game_submitinfo.user_id', '=', 'users.user_id')
                        ->orderBy('users.userid', $direction);
                }),
            Column::make('Date')
                ->sortable(function (Builder $query, $direction) {
                    // Validate direction to avoid SQL injections
                    $d = $direction === 'asc' ? 'asc' : 'desc';

                    return $query->orderByRaw("convert(timestamp, unsigned) $d");
                }),
            Column::make('Reviewed', 'game_done')->sortable(),
            Column::blank(),
        ];
    }

    public function query(): Builder
    {
        return GameSubmitInfo::query()
            ->when(
                $this->getFilter('search'),
                fn ($query, $term) => $query->whereHas('user', function (Builder $query) use ($term) {
                    return $query->where('userid', 'like', "%{$term}%");
                })
                    ->orWhereHas('game', function (Builder $query) use ($term) {
                        return $query->where('game_name', 'like', "%{$term}%");
                    })
            )
            ->when(
                $this->getFilter('processed'),
                fn ($query, $term) => $query->where('game_done', $term === 'yes' ? '=' : '!=', GameSubmitInfo::SUBMISSION_REVIEWED)
            )
            ->when(
                $this->getFilter('attachments'),
                fn ($query, $term) => $term === 'yes' ? $query->has('screenshots') : $query->doesntHave('screenshots')
            );
    }

    public function filters(): array
    {
        return [
            'processed' => Filter::make('Reviewed')
                ->select([
                    ''    => 'Any',
                    'yes' => 'Yes',
                    'no'  => 'No',
                ]),
            'attachments' => Filter::make('Has attachments')
                ->select([
                    ''    => 'Any',
                    'yes' => 'Yes',
                    'no'  => 'No',
                ]),
        ];
    }

    public function getTableRowUrl($row): string
    {
        return route('admin.games.submissions.show', $row);
    }

    public function rowView(): string
    {
        return 'admin.games.submissions.list_row';
    }
}
