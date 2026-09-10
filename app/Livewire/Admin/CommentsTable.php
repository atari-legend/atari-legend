<?php

namespace App\Livewire\Admin;

use App\Helpers\Helper;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Columns\LinkColumn;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

/**
 * One comment table per section. builder() returns one Eloquent builder, and the
 * four comment tables cannot produce one between them, so each section gets a
 * subclass naming its model, its table and its routes.
 */
abstract class CommentsTable extends DataTableComponent
{
    /**
     * @return class-string<\App\Models\Comment> The model this table lists
     */
    abstract protected function model(): string;

    /**
     * The relation on User that counts this section's comments, for the author
     * filter.
     */
    abstract protected function userRelation(): string;

    /**
     * Route prefix: `games`, `articles`, `interviews` or `reviews`.
     */
    abstract protected function section(): string;

    /**
     * What the Target column calls the thing a comment is on.
     */
    abstract protected function targetHeading(): string;

    /**
     * The model's table, which the sorts and the author filter qualify with.
     */
    protected function table(): string
    {
        return (new ($this->model()))->getTable();
    }

    public function configure(): void
    {
        $this->setPrimaryKey('id');
        $this->setDefaultSort('created_at');
    }

    public function columns(): array
    {
        $table = $this->table();

        return [
            Column::make('User')
                ->label(fn ($row) => Helper::user($row->user))
                ->sortable(function (Builder $query, $direction) use ($table) {
                    return $query->join('users', "{$table}.user_id", '=', 'users.id')
                        ->orderBy('users.userid', $direction);
                }),
            // The column is qualified because the User sort above joins
            // `users`, which now carries a created_at of its own.
            Column::make('Date', 'created_at')
                ->format(fn ($value) => $value?->toDayDateTimeString() ?? '-')
                ->sortable(
                    fn (Builder $query, $direction) => $query->orderBy("{$table}.created_at", $direction)
                ),
            Column::make($this->targetHeading())
                ->label(fn ($row) => $row->target),
            LinkColumn::make('Content')
                ->title(fn ($row) => Str::words($row->text, 20))
                ->location(fn ($row) => route("admin.users.comments.{$this->section()}.edit", $row))
                ->searchable(
                    fn ($query, $term) => $query->where('text', 'like', '%' . $term . '%')
                ),
            Column::make('Actions')
                ->label(
                    fn ($row) => view('admin.users.comments.datatable_actions')
                        ->with(['row' => $row, 'section' => $this->section()])
                ),

        ];
    }

    public function builder(): Builder
    {
        return $this->model()::select($this->table() . '.*');
    }

    public function filters(): array
    {
        $table = $this->table();

        $authors = User::has($this->userRelation())
            ->orderBy('userid')
            ->get()
            ->mapWithKeys(function ($user) {
                return [strval($user->getKey()) => $user->userid];
            })->all();
        $authors = ['' => 'Any'] + $authors;

        return [
            'author' => SelectFilter::make('Author')
                ->options($authors)
                ->filter(fn ($query, $term) => $query->where("{$table}.user_id", '=', $term)),

        ];
    }
}
