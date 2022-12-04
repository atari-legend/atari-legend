<?php

namespace App\Http\Livewire\Admin;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filter;

class CommentsTable extends DataTableComponent
{
    public string $primaryKey = 'comment_id';

    public string $defaultSortColumn = 'timestamp';

    public function columns(): array
    {
        return [
            Column::make('User')
                ->sortable(function (Builder $query, $direction) {
                    return $query->join('users', 'comments.user_id', '=', 'users.user_id')
                        ->orderBy('users.userid', $direction);
                }),
            Column::make('Date')
                ->sortable(function (Builder $query, $direction) {
                    // Validate direction to avoid SQL injections
                    $d = $direction === 'asc' ? 'asc' : 'desc';

                    return $query->orderByRaw("convert(`timestamp`, unsigned) $d");
                }),
            Column::make('Type'),
            Column::make('Content'),
            Column::blank(),
        ];
    }

    public function query(): Builder
    {
        return Comment::query()
            ->when(
                $this->getFilter('search'),
                fn ($query, $term) => $query->where('comment', 'like', '%' . $term . '%')
            )
            ->when(
                $this->getFilter('type'),
                fn ($query, $term) => $query->has($term)
            )->when(
                $this->getFilter('author'),
                fn ($query, $term) => $query->where('comments.user_id', '=', $term)
            );
    }

    public function filters(): array
    {
        $authors = User::has('comments')
            ->orderBy('userid')
            ->get()
            ->mapWithKeys(function ($user) {
                return [strval($user->user_id) => $user->userid];
            })->all();
        $authors = ['' => 'Any'] + $authors;

        return [
            'type' => Filter::make('Type')
                ->select([
                    ''           => 'Any',
                    'games'      => 'Game',
                    'reviews'    => 'Review',
                    'interviews' => 'Interview',
                    'articles'   => 'Article',
                ]),
            'author' => Filter::make('Author')
                ->select($authors),

        ];
    }

    public function getTableRowUrl($row): string
    {
        return route('admin.users.comments.edit', $row);
    }

    public function rowView(): string
    {
        return 'admin.users.comments.list_row';
    }
}
