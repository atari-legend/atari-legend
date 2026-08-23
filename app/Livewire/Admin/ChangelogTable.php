<?php

namespace App\Livewire\Admin;

use App\Helpers\Helper;
use App\Models\Changelog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\DateFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

class ChangelogTable extends DataTableComponent
{
    public function configure(): void
    {
        $this->setPrimaryKey('id');
        $this->setDefaultSort('timestamp', 'desc');
    }

    public function columns(): array
    {
        return [
            Column::make('Action', 'action')
                ->label(
                    fn ($row) => view('admin.others.changelog.datatable_action')
                        ->with(['action' => $row->action])
                )
                ->sortable(),
            Column::make('User')
                ->label(fn ($row) => Helper::user($row->user))
                ->sortable(function (Builder $query, $direction) {
                    // Left join so entries without a user (user_id -1) are kept
                    return $query->leftJoin('users', 'change_log.user_id', '=', 'users.id')
                        ->orderBy('users.userid', $direction);
                }),
            Column::make('Section', 'section')
                ->sortable()
                ->searchable(),
            Column::make('Item', 'section_name')
                ->sortable()
                ->searchable(),
            Column::make('Sub-section', 'sub_section')
                ->sortable()
                ->searchable(),
            Column::make('Sub-item', 'sub_section_name')
                ->sortable()
                ->searchable(),
            Column::make('Date', 'timestamp')
                ->format(
                    fn ($value) => $value
                        ? '<abbr title="' . e($value->format('F j, Y H:i')) . '">'
                            . e($value->diffForHumans()) . '</abbr>'
                        : '-'
                )
                ->html()
                ->sortable(),
        ];
    }

    public function builder(): Builder
    {
        // Qualify the select: sorting on User joins `users`, which now also has an `id`
        return Changelog::query()
            ->select('change_log.*')
            ->with('user');
    }

    public function filters(): array
    {
        return [
            'from' => DateFilter::make('Date from', 'from')
                ->filter(
                    fn (Builder $query, string $value) => $query->where(
                        'change_log.timestamp',
                        '>=',
                        Carbon::parse($value)->startOfDay()->timestamp
                    )
                ),
            'to' => DateFilter::make('Date to', 'to')
                ->filter(
                    fn (Builder $query, string $value) => $query->where(
                        'change_log.timestamp',
                        '<=',
                        Carbon::parse($value)->endOfDay()->timestamp
                    )
                ),
            'action' => SelectFilter::make('Action')
                ->options($this->distinctOptions('action'))
                ->filter(
                    fn (Builder $query, string $term) => $query->where('change_log.action', '=', $term)
                ),
            'section' => SelectFilter::make('Section')
                ->options($this->distinctOptions('section'))
                ->filter(
                    fn (Builder $query, string $term) => $query->where('change_log.section', '=', $term)
                ),
            'sub_section' => SelectFilter::make('Sub-section', 'sub_section')
                ->options($this->subSectionOptions())
                ->filter(
                    fn (Builder $query, string $term) => $query->where('change_log.sub_section', '=', $term)
                ),
            'user' => SelectFilter::make('User')
                ->options($this->userOptions())
                ->filter(
                    fn (Builder $query, string $term) => $query->where('change_log.user_id', '=', $term)
                ),
        ];
    }

    /**
     * Distinct values of a change_log column, as filter options.
     *
     * @param  string  $column  Column to list the values of
     * @return array<string, string> Options, keyed by value
     */
    private function distinctOptions(string $column): array
    {
        $values = DB::table('change_log')
            ->distinct()
            ->where($column, '!=', '')
            ->orderBy($column)
            ->pluck($column)
            ->all();

        return ['' => 'Any'] + array_combine($values, $values);
    }

    /**
     * Sub-section options, narrowed down to the selected section if there is one.
     *
     * @return array<string, string> Options, keyed by value
     */
    private function subSectionOptions(): array
    {
        // Read the raw property rather than getAppliedFilterWithValue(): the latter
        // calls filters() again, which would recurse.
        $section = $this->filterComponents['section'] ?? null;

        $values = DB::table('change_log')
            ->distinct()
            ->where('sub_section', '!=', '')
            ->when($section, fn ($query) => $query->where('section', '=', $section))
            ->orderBy('sub_section')
            ->pluck('sub_section')
            ->all();

        return ['' => 'Any'] + array_combine($values, $values);
    }

    /**
     * Users that have changelog entries, as filter options.
     *
     * @return array<string, string> Options, keyed by user id
     */
    private function userOptions(): array
    {
        $users = User::has('changelogs')
            ->orderBy('userid')
            ->get()
            ->mapWithKeys(function ($user) {
                return [strval($user->getKey()) => $user->userid];
            })->all();

        return ['' => 'Any'] + $users;
    }
}
