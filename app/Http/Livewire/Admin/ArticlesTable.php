<?php

namespace App\Http\Livewire\Admin;

use App\Helpers\Helper;
use App\Models\Article;
use App\Models\User;
use Carbon\Carbon;
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
        $this->setPrimaryKey('article_id');
        $this->setDefaultSort('article_date', 'desc');
    }

    public function columns(): array
    {
        return [
            LinkColumn::make('Title')
                ->title(
                    fn($row) => $row->article_title
                )
                ->location(
                    fn($row) => route('admin.articles.articles.edit', $row)
                )
                ->searchable(
                    fn($query, $term) => $query->where('article_title', 'like', "%{$term}%")
                        ->orWhere('article_text', 'like', "%{$term}%")
                        ->orWhere('article_intro', 'like', "%{$term}%")
                )
                ->sortable(),
            Column::make('Date')
                ->label(
                    fn($row) => $row->article_date
                        ? Carbon::createFromTimestamp($row->article_date)->toFormattedDateString()
                        : '-'
                )
                ->sortable(
                    fn(Builder $query, $direction) => $query->orderBy('article_date', $direction)
                ),
            Column::make('Author')
                ->label(fn($row) => Helper::user($row->user)),
            BooleanColumn::make('Draft', 'draft')
                ->sortable(),
            Column::make('Actions')
                ->label(
                    fn($row) => view('admin.articles.articles.datatable_actions')->with(['row' => $row])
                ),
        ];
    }

    public function builder(): Builder
    {
        return Article::select()
            ->leftJoin('article_text', 'article_text.article_id', '=', 'article_main.article_id');
    }

    public function filters(): array
    {
        $authors = User::has('articles')
            ->orderBy('userid')
            ->get()
            ->mapWithKeys(function ($user) {
                return [strval($user->user_id) => $user->userid];
            })->all();
        $authors = ['' => 'Any'] + $authors;

        return [
            'author' => SelectFilter::make('Author')
                ->options($authors)
                ->filter(fn($query, $term) => $query->where('user_id', '=', $term)),

        ];
    }
}
