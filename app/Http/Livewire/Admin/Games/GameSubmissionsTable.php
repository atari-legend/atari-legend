<?php

namespace App\Http\Livewire\Admin\Games;

use App\Helpers\Helper;
use App\Models\GameSubmitInfo;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Columns\BooleanColumn;
use Rappasoft\LaravelLivewireTables\Views\Columns\LinkColumn;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

class GameSubmissionsTable extends DataTableComponent
{
    public array $filters = [
        'processed' => 'no',
    ];

    public function configure(): void
    {
        $this->setPrimaryKey('game_submitinfo_id');
    }

    public function columns(): array
    {
        return [
            LinkColumn::make('Game')
                ->title(fn ($row) => $row->game?->game_name)
                ->location(fn ($row) => route('admin.games.submissions.show', $row))
                ->searchable(
                    fn (Builder $query, string $term) => $query->whereHas('user', function (Builder $subQuery) use ($term) {
                        return $subQuery->where('userid', 'like', "%{$term}%");
                    })
                        ->orWhereHas('game', function (Builder $subQuery) use ($term) {
                            return $subQuery->where('game_name', 'like', "%{$term}%");
                        })
                )
                ->sortable(function (Builder $query, $direction) {
                    return $query->join('game', 'game_submitinfo.game_id', '=', 'game.game_id')
                        ->orderBy('game.game_name', $direction);
                }),
            Column::make('User')
                ->label(fn ($row) => Helper::user($row->user))
                ->sortable(function (Builder $query, $direction) {
                    return $query->join('users', 'game_submitinfo.user_id', '=', 'users.user_id')
                        ->orderBy('users.userid', $direction);
                }),
            Column::make('Date')
                ->label(
                    fn ($row) => $row->timestamp
                        ? Carbon::createFromTimestamp($row->timestamp)->toDayDateTimeString()
                        : '-'
                )
                ->sortable(function (Builder $query, $direction) {
                    // Validate direction to avoid SQL injections
                    $d = $direction === 'asc' ? 'asc' : 'desc';

                    return $query->orderByRaw("convert(timestamp, unsigned) $d");
                }),
            BooleanColumn::make('Reviewed', 'game_done')
                ->setCallback(fn ($value) => $value === GameSubmitInfo::SUBMISSION_REVIEWED)
                ->sortable(),
            Column::make('Actions')
                ->label(
                    fn ($row) => view('admin.games.submissions.datatable_actions')->with(['row' => $row])
                ),

        ];
    }

    public function builder(): Builder
    {
        return GameSubmitInfo::select();
    }

    public function filters(): array
    {
        return [
            'processed' => SelectFilter::make('Reviewed')
                ->options([
                    ''    => 'Any',
                    'yes' => 'Yes',
                    'no'  => 'No',
                ])
                ->filter(
                    fn (Builder $query, string $term) => $query->where('game_done', $term === 'yes' ? '=' : '!=', GameSubmitInfo::SUBMISSION_REVIEWED)
                ),
            'attachments' => SelectFilter::make('Has attachments')
                ->options([
                    ''    => 'Any',
                    'yes' => 'Yes',
                    'no'  => 'No',
                ])
                ->filter(
                    fn (Builder $query, string $term) => $term === 'yes' ? $query->has('screenshots') : $query->doesntHave('screenshots')
                ),
        ];
    }

    public function getTableRowUrl($row): string
    {
        return route('admin.games.submissions.show', $row);
    }
}
