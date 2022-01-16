<?php

namespace App\Http\Livewire\Admin;

use App\Models\NewsSubmission;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class NewsSubmissionsTable extends DataTableComponent
{
    public string $primaryKey = 'news_submission_id';

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
            Column::make('Text')
                ->sortable(function (Builder $query, $direction) {
                    return $query->orderBy('news_text', $direction);
                }),
            Column::make('Author'),
            Column::blank(),
        ];
    }

    public function query(): Builder
    {
        return NewsSubmission::query()
            ->when(
                $this->getFilter('search'),
                fn ($query, $term) => $query->where('news_headline', 'like', "%{$term}%")
                    ->orWhere('news_text', 'like', "%{$term}%")
            );
    }

    public function filters(): array
    {
        return [];
    }

    public function rowView(): string
    {
        return 'admin.news.submissions.list_row';
    }
}
