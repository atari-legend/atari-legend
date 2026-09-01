<?php

namespace App\Livewire\Admin;

use App\Helpers\Helper;
use App\Models\NewsSubmission;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class NewsSubmissionsTable extends DataTableComponent
{
    public function configure(): void
    {
        $this->setPrimaryKey('id');
        $this->setDefaultSort('date', 'desc');
    }

    public function columns(): array
    {
        return [
            Column::make('Headline', 'headline')
                // The callback used to return another closure rather than
                // constrain the query, so searching submissions did nothing.
                ->searchable(fn ($query, $term) => $query->where('headline', 'like', "%{$term}%")
                    ->orWhere('text', 'like', "%{$term}%"))
                ->sortable(),
            // Sortable so that configure()'s default sort on this column takes
            // effect - without it the queue came back in insertion order rather
            // than newest first.
            Column::make('Date', 'date')
                ->format(fn ($value) => $value?->toDayDateTimeString() ?? '-')
                ->sortable(),
            Column::make('Text', 'text')
                ->sortable()
                ->format(fn ($value) => Helper::bbCode(stripslashes(nl2br(e($value)))))
                ->html(),
            Column::make('Author')
                ->label(fn ($row) => Helper::user($row->user)),
            Column::make('Actions')
                ->label(
                    fn ($row) => view('admin.news.submissions.datatable_actions')->with(['row' => $row])
                ),

        ];
    }

    public function builder(): Builder
    {
        return NewsSubmission::select('news_submissions.*');
    }
}
