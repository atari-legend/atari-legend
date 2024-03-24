<?php

namespace App\Http\Livewire\Admin;

use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filter;

class ReviewsTable extends DataTableComponent
{
    public string $primaryKey = 'review_id';

    public string $defaultSortColumn = 'review_date';
    public string $defaultSortDirection = 'desc';

    public function columns(): array
    {
        return [
            Column::make('Game')
                ->sortable(function (Builder $query, $direction) {
                    return $query
                        ->leftJoin('review_game', 'review_game.review_id', 'review_main.review_id')
                        ->leftJoin('game', 'review_game.game_id', 'game.game_id')
                        ->orderBy('game_name', $direction);
                }),
            Column::make('Date')
                ->sortable(function (Builder $query, $direction) {
                    return $query->orderBy('review_date', $direction);
                }),
            Column::make('Author'),
            Column::blank(),
        ];
    }

    public function query(): Builder
    {
        return Review::select('review_main.*')
            ->where('review_edit', '!=', 1)
            ->when(
                $this->getFilter('search'),
                fn ($query, $term) => $query
                    ->leftJoin('review_game', 'review_game.review_id', 'review_main.review_id')
                    ->leftJoin('game', 'review_game.game_id', 'game.game_id')
                    ->where('game.game_name', 'like', "%{$term}%")
            )->when(
                $this->getFilter('author'),
                fn ($query, $term) => $query->where('review_main.user_id', '=', $term)
            );
    }

    public function filters(): array
    {
        $authors = User::has('reviews')
            ->orderBy('userid')
            ->get()
            ->mapWithKeys(function ($user) {
                return [strval($user->user_id) => $user->userid];
            })->all();
        $authors = ['' => 'Any'] + $authors;

        return [
            'author' => Filter::make('Author')
                ->select($authors),

        ];
    }

    public function getTableRowUrl($row): string
    {
        return route('admin.reviews.reviews.edit', $row);
    }

    public function rowView(): string
    {
        return 'admin.reviews.reviews.list_row';
    }
}
