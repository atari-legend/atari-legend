<?php

namespace App\Livewire\Admin\Games;

use App\Helpers\Helper;
use App\Models\GameSubmission;
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
        $this->setPrimaryKey('id');
    }

    public function columns(): array
    {
        return [
            LinkColumn::make('Game')
                ->title(fn ($row) => $row->game?->name)
                ->location(fn ($row) => route('admin.games.submissions.show', $row))
                ->searchable(
                    fn (Builder $query, string $term) => $query->whereHas('user', function (Builder $subQuery) use ($term) {
                        return $subQuery->where('userid', 'like', "%{$term}%");
                    })
                        ->orWhereHas('game', function (Builder $subQuery) use ($term) {
                            return $subQuery->where('name', 'like', "%{$term}%");
                        })
                )
                ->sortable(function (Builder $query, $direction) {
                    return $query->join('games', 'game_submissions.game_id', '=', 'games.id')
                        ->orderBy('games.name', $direction);
                }),
            Column::make('User')
                ->label(fn ($row) => Helper::user($row->user))
                ->sortable(function (Builder $query, $direction) {
                    return $query->join('users', 'game_submissions.user_id', '=', 'users.id')
                        ->orderBy('users.userid', $direction);
                }),
            // The column is qualified because the Game and User sorts above
            // join `games` and `users`, both of which carry a created_at.
            Column::make('Date', 'created_at')
                ->format(fn ($value) => $value?->toDayDateTimeString() ?? '-')
                ->sortable(
                    fn (Builder $query, $direction) => $query->orderBy('game_submissions.created_at', $direction)
                ),
            BooleanColumn::make('Reviewed', 'reviewed')
                ->sortable(),
            Column::make('Actions')
                ->label(
                    fn ($row) => view('admin.games.submissions.datatable_actions')->with(['row' => $row])
                ),

        ];
    }

    public function builder(): Builder
    {
        return GameSubmission::select('game_submissions.*');
    }

    public function filters(): array
    {
        return [
            // The keys are passed explicitly because the default above is keyed
            // by them. A filter's key is Str::snake() of its *name* unless one
            // is given, so these two were keyed 'reviewed' and 'has_attachments'
            // - 'processed' matched neither, the default was silently dropped,
            // and the queue opened on every submission ever made rather than on
            // the ones nobody has looked at yet.
            'processed' => SelectFilter::make('Reviewed', 'processed')
                ->options([
                    ''    => 'Any',
                    'yes' => 'Yes',
                    'no'  => 'No',
                ])
                ->filter(
                    fn (Builder $query, string $term) => $query->where('reviewed', $term === 'yes')
                ),
            'attachments' => SelectFilter::make('Has attachments', 'attachments')
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
