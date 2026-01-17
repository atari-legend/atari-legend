<?php

namespace App\Livewire\Admin;

use App\Helpers\Helper;
use App\Models\Interview;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Columns\BooleanColumn;
use Rappasoft\LaravelLivewireTables\Views\Columns\LinkColumn;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

class InterviewsTable extends DataTableComponent
{
    public function configure(): void
    {
        $this->setPrimaryKey('interview_id');
        $this->setDefaultSort('interview_date', 'desc');
    }

    public function columns(): array
    {
        return [
            LinkColumn::make('Individual')
                ->title(
                    fn ($row) => $row->ind_name
                )
                ->location(
                    fn ($row) => route('admin.interviews.interviews.edit', $row)
                )
                ->searchable(
                    fn ($query, $term) => $query->where('ind_name', 'like', "%{$term}%")
                        ->orWhere('interview_text', 'like', "%{$term}%")
                        ->orWhere('interview_intro', 'like', "%{$term}%")
                )
                ->sortable(
                    fn (Builder $query, $direction) => $query->orderBy('ind_name', $direction)
                ),
            Column::make('Date')
                ->label(
                    fn ($row) => $row->texts->first()?->interview_date
                        ? $row->texts->first()->interview_date->toFormattedDateString()
                        : '-'
                )
                ->sortable(
                    fn (Builder $query, $direction) => $query->orderBy('interview_date', $direction)
                ),
            Column::make('Author')
                ->label(fn ($row) => Helper::user($row->user)),
            BooleanColumn::make('Draft', 'draft')
                ->sortable(),
            Column::make('Actions')
                ->label(
                    fn ($row) => view('admin.interviews.interviews.datatable_actions')->with(['row' => $row])
                ),
        ];
    }

    public function builder(): Builder
    {
        return Interview::select()
            ->leftJoin('interview_text', 'interview_text.interview_id', '=', 'interview_main.interview_id')
            ->leftJoin('individuals', 'individuals.ind_id', '=', 'interview_main.ind_id');
    }

    public function filters(): array
    {
        $authors = User::has('interviews')
            ->orderBy('userid')
            ->get()
            ->mapWithKeys(function ($user) {
                return [strval($user->user_id) => $user->userid];
            })->all();
        $authors = ['' => 'Any'] + $authors;

        return [
            'author' => SelectFilter::make('Author')
                ->options($authors)
                ->filter(fn ($query, $term) => $query->where('interview_main.user_id', '=', $term)),
            'draft'  => SelectFilter::make('Draft')
                ->options(['' => 'Any', true => 'Yes', false => 'No'])
                ->filter(fn ($query, $term) => $query->where('draft', '=', $term)),
        ];
    }
}
