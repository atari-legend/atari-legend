<?php

namespace App\Livewire\Admin;

use App\Helpers\Helper;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Columns\LinkColumn;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

class CommentsTable extends DataTableComponent
{
    public function configure(): void
    {
        $this->setPrimaryKey('id');
        $this->setDefaultSort('created_at');
    }

    public function columns(): array
    {
        return [
            Column::make('User')
                ->label(fn ($row) => Helper::user($row->user))
                ->sortable(function (Builder $query, $direction) {
                    return $query->join('users', 'comments.user_id', '=', 'users.id')
                        ->orderBy('users.userid', $direction);
                }),
            // The column is qualified because the User sort above joins
            // `users`, which now carries a created_at of its own.
            Column::make('Date', 'created_at')
                ->format(fn ($value) => $value?->toDayDateTimeString() ?? '-')
                ->sortable(
                    fn (Builder $query, $direction) => $query->orderBy('comments.created_at', $direction)
                ),
            Column::make('Type')
                ->label(
                    fn ($row) => '<div class="text-muted">' . Str::ucfirst($row->type) . '</div>'
                        . $row->target
                )
                ->html(),
            LinkColumn::make('Content')
                ->title(fn ($row) => Str::words($row->text, 20))
                ->location(fn ($row) => route('admin.users.comments.edit', $row))
                ->searchable(
                    fn ($query, $term) => $query->where('text', 'like', '%' . $term . '%')
                ),
            Column::make('Actions')
                ->label(
                    fn ($row) => view('admin.users.comments.datatable_actions')->with(['row' => $row])
                ),

        ];
    }

    public function builder(): Builder
    {
        return Comment::select('comments.*');
    }

    public function filters(): array
    {
        $authors = User::has('comments')
            ->orderBy('userid')
            ->get()
            ->mapWithKeys(function ($user) {
                return [strval($user->getKey()) => $user->userid];
            })->all();
        $authors = ['' => 'Any'] + $authors;

        return [
            'type' => SelectFilter::make('Type')
                ->options([
                    ''           => 'Any',
                    'games'      => 'Game',
                    'reviews'    => 'Review',
                    'interviews' => 'Interview',
                    'articles'   => 'Article',
                ])
                ->filter(fn ($query, $term) => $query->has($term)),
            'author' => SelectFilter::make('Author')
                ->options($authors)
                ->filter(fn ($query, $term) => $query->where('comments.user_id', '=', $term)),

        ];
    }
}
