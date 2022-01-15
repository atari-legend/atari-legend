<?php

namespace App\Http\Livewire\Admin;

use App\Models\News;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filter;

class NewsTable extends DataTableComponent
{
    public string $primaryKey = 'news_id';

    public string $defaultSortColumn = 'news_date';
    public string $defaultSortDirection = 'desc';

    public function columns(): array
    {
        return [
            Column::make('Headline', 'news_headline')->sortable(),
            Column::make('Date')
                ->sortable(function (Builder $query, $direction) {
                    return $query->orderBy('news_date', $direction);
                }),
            Column::make('Image')
                ->sortable(function (Builder $query, $direction) {
                    return $query->orderBy('news_image_ext', $direction);
                }),
            Column::make('Author'),
            Column::blank(),
        ];
    }

    public function query(): Builder
    {
        return News::select('news.*')
            ->leftJoin('news_image', 'news.news_image_id', '=', 'news_image.news_image_id')
            ->when(
                $this->getFilter('search'),
                fn ($query, $term) => $query->where('news_headline', 'like', "%{$term}%")
                    ->orWhere('news_text', 'like', "%{$term}%")
            )->when(
                $this->getFilter('author'),
                fn ($query, $term) => $query->where('news.user_id', '=', $term)
            );
    }

    public function filters(): array
    {
        $authors = User::has('news')
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
        return route('admin.news.news.edit', $row);
    }

    public function rowView(): string
    {
        return 'admin.news.news.list_row';
    }
}
