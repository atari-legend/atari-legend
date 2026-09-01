<?php

namespace App\Livewire\Admin;

use App\Helpers\Helper;
use App\Models\Article;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Columns\BooleanColumn;
use Rappasoft\LaravelLivewireTables\Views\Columns\LinkColumn;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

class ArticlesTable extends DataTableComponent
{
    public function configure(): void
    {
        $this->setPrimaryKey('id');
        $this->setDefaultSort('date', 'desc');
    }

    public function columns(): array
    {
        return [
            LinkColumn::make('Title')
                ->title(
                    fn ($row) => $row->title
                )
                ->location(
                    fn ($row) => route('admin.articles.articles.edit', $row)
                )
                ->searchable(
                    fn ($query, $term) => $query->where('title', 'like', "%{$term}%")
                        ->orWhere('text', 'like', "%{$term}%")
                        ->orWhere('intro', 'like', "%{$term}%")
                )
                ->sortable(),
            Column::make('Date')
                ->label(
                    fn ($row) => $row->date?->toFormattedDateString() ?? '-'
                )
                ->sortable(
                    fn (Builder $query, $direction) => $query->orderBy('date', $direction)
                ),
            Column::make('Author')
                ->label(fn ($row) => Helper::user($row->user)),
            BooleanColumn::make('Draft', 'draft')
                ->sortable(),
            Column::make('Actions')
                ->label(
                    fn ($row) => view('admin.articles.articles.datatable_actions')->with(['row' => $row])
                ),
        ];
    }

    public function builder(): Builder
    {
        return Article::query()->select('articles.*');
    }

    public function filters(): array
    {
        $authors = User::has('articles')
            ->orderBy('userid')
            ->get()
            ->mapWithKeys(function ($user) {
                return [strval($user->getKey()) => $user->userid];
            })->all();
        $authors = ['' => 'Any'] + $authors;

        return [
            'author' => SelectFilter::make('Author')
                ->options($authors)
                ->filter(fn ($query, $term) => $query->where('user_id', '=', $term)),

        ];
    }
}
