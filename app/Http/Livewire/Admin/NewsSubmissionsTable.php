<?php

namespace App\Http\Livewire\Admin;

use App\Helpers\Helper;
use App\Models\NewsSubmission;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class NewsSubmissionsTable extends DataTableComponent
{
    public function configure(): void
    {
        $this->setPrimaryKey('news_submission_id');
        $this->setDefaultSort('news_date', 'desc');
    }

    public function columns(): array
    {
        return [
            Column::make('Headline', 'news_headline')
                ->searchable(fn($row) => fn($query, $term) => $query->where('news_headline', 'like', "%{$term}%")
                    ->orWhere('news_text', 'like', "%{$term}%"))
                ->sortable(),
            Column::make('Date', 'news_date')
                ->format(fn($value) => $value?->toDayDateTimeString() ?? '-'),
            Column::make('Text', 'news_text')
                ->sortable()
                ->format(fn($value) => Helper::bbCode(stripslashes(nl2br(e($value)))))
                ->html(),
            Column::make('Author')
                ->label(fn($row) => Helper::user($row->user)),
            Column::make('Actions')
                ->label(
                    fn($row) => view('admin.news.submissions.datatable_actions')->with(['row' => $row])
                ),

        ];
    }

    public function builder(): Builder
    {
        return NewsSubmission::select();
    }
}
