<?php

namespace App\Livewire\Admin;

use App\Helpers\Helper;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Columns\BooleanColumn;
use Rappasoft\LaravelLivewireTables\Views\Columns\LinkColumn;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

class ReviewsTable extends DataTableComponent
{
    // Whether to display submissions or not
    public int $submissions = Review::REVIEW_PUBLISHED;

    public function configure(): void
    {
        $this->setPrimaryKey('review_id');
        $this->setDefaultSort('review_date', 'desc');
    }

    public function columns(): array
    {
        return [
            LinkColumn::make('Game')
                ->title(
                    fn ($row) => $row->game_name
                )
                ->location(
                    fn ($row) => route('admin.reviews.reviews.edit', $row)
                )
                ->searchable(
                    fn ($query, $term) => $query->where('game_name', 'like', "%{$term}%")
                        ->orWhere('review_text', 'like', "%{$term}%")
                )
                ->sortable(
                    fn (Builder $query, $direction) => $query->orderBy('game_name', $direction)
                ),
            Column::make('Date')
                ->label(
                    fn ($row) => $row->review_date
                        ? $row->review_date->toFormattedDateString()
                        : '-'
                )
                ->sortable(
                    fn (Builder $query, $direction) => $query->orderBy('review_date', $direction)
                ),
            Column::make('Author')
                ->label(fn ($row) => Helper::user($row->user)),
            BooleanColumn::make('Draft', 'draft')
                ->sortable(),
            Column::make('Actions')
                ->label(
                    fn ($row) => view('admin.reviews.reviews.datatable_actions')->with(['row' => $row])
                ),
        ];
    }

    public function builder(): Builder
    {
        return Review::select()
            ->where('review_edit', '=', $this->submissions)
            ->leftJoin('review_game', 'review_game.review_id', '=', 'review_main.review_id')
            ->leftJoin('game', 'review_game.game_id', '=', 'game.game_id');
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
            'author' => SelectFilter::make('Author')
                ->options($authors)
                ->filter(fn ($query, $term) => $query->where('user_id', '=', $term)),
            'draft'  => SelectFilter::make('Draft')
                ->options(['' => 'Any', true => 'Yes', false => 'No'])
                ->filter(fn ($query, $term) => $query->where('draft', '=', $term)),
        ];
    }
}
