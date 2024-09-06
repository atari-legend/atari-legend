<?php

namespace App\Http\Livewire\Admin;

use App\Helpers\Helper;
use App\Models\News;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Columns\LinkColumn;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

class NewsTable extends DataTableComponent
{
    public function configure(): void
    {
        $this->setPrimaryKey('news_id');
        $this->setDefaultSort('news_date', 'desc');
    }

    public function columns(): array
    {
        return [
            LinkColumn::make('Headline', 'news_headline')
                ->title(fn($row) => $row->news_headline)
                ->location(fn($row) => route('admin.news.news.edit', $row))
                ->searchable(
                    fn(Builder $query, string $term) => $query->where('news_headline', 'like', "%{$term}%")
                        ->orWhere('news_text', 'like', "%{$term}%")
                )
                ->sortable(),
            Column::make('Date', 'news_date')
                ->format(fn($value) => $value?->toDayDateTimeString() ?? '-')
                ->sortable(),
            Column::make('Image')
                ->sortable(function (Builder $query, $direction) {
                    return $query->orderBy('news_image_ext', $direction);
                })
                ->label(
                    fn($row) => $row->news_image
                        ? '<img class="shadow-sm" style="max-height: 2rem" src="' . $row->news_image . '" alt="News image">'
                        : ''
                )
                ->html(),
            Column::make('Author')
                ->label(fn($row) => Helper::user($row->user)),
            Column::make('Actions')
                ->label(
                    fn($row) => view('admin.news.news.datatable_actions')->with(['row' => $row])
                ),
        ];
    }

    public function builder(): Builder
    {
        return News::query()
            ->leftJoin('news_image', 'news.news_image_id', '=', 'news_image.news_image_id')
            ->select();
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
            'author' => SelectFilter::make('Author')
                ->options($authors)
                ->filter(fn(Builder $query, string $term) => $query->where('news.user_id', '=', $term)),

        ];
    }
}
