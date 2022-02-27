<?php

namespace App\Http\Livewire\Admin;

use App\Models\Article;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filter;

class ArticlesTable extends DataTableComponent
{
    public string $primaryKey = 'article_id';

    public string $defaultSortColumn = 'article_date';
    public string $defaultSortDirection = 'desc';

    public function columns(): array
    {
        return [
            Column::make('Title', 'article_title')->sortable(),
            Column::make('Date')
                ->sortable(function (Builder $query, $direction) {
                    return $query->orderBy('article_date', $direction);
                }),
            Column::make('Author'),
            Column::make('Draft', 'draft')->sortable(),
            Column::blank(),
        ];
    }

    public function query(): Builder
    {
        return Article::select('article_main.*')
            ->leftJoin('article_text', 'article_text.article_id', '=', 'article_main.article_id')
            ->when(
                $this->getFilter('search'),
                fn ($query, $term) => $query->where('article_title', 'like', "%{$term}%")
                    ->orWhere('article_text', 'like', "%{$term}%")
                    ->orWhere('article_intro', 'like', "%{$term}%")
            )->when(
                $this->getFilter('author'),
                fn ($query, $term) => $query->where('user_id', '=', $term)
            );
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
            'author' => Filter::make('Author')
                ->select($authors),

        ];
    }

    public function getTableRowUrl($row): string
    {
        return route('admin.articles.articles.edit', $row);
    }

    public function rowView(): string
    {
        return 'admin.articles.articles.list_row';
    }
}
