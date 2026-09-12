<?php

namespace App\Livewire\Admin;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Columns\LinkColumn;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

class UsersTable extends DataTableComponent
{
    public function configure(): void
    {
        $this->setPrimaryKey('id');
        $this->setDefaultSort('userid');
    }

    public function columns(): array
    {
        return [
            LinkColumn::make('Name', 'userid')
                ->title(fn ($row) => $row->userid)
                ->location(fn ($row) => route('admin.users.users.edit', $row))
                ->searchable(
                    fn (Builder $query, $term) => $query->where('userid', 'like', '%' . $term . '%')
                )
                ->sortable(
                    fn (Builder $query, string $direction) => $query->orderBy('userid', $direction)
                ),
            Column::make('Avatar')
                ->label(
                    fn ($row) => $row->avatar
                        ? '<img class="shadow-sm" style="max-height: 2rem" alt="User avatar" src="' . $row->avatar . '">'
                        : ''
                )
                ->html()
                ->sortable(function (Builder $query, $direction) {
                    return $query->orderBy('imgext', $direction);
                }),
            Column::make('Join date', 'created_at')
                ->format(fn ($value) => $value?->toDayDateTimeString() ?? '-')
                ->sortable(
                    fn (Builder $query, $direction) => $query->orderBy('created_at', $direction)
                ),
            Column::make('Last visit', 'last_visit_at')
                ->format(fn ($value) => $value?->toDayDateTimeString() ?? '-')
                ->sortable(
                    fn (Builder $query, $direction) => $query->orderBy('last_visit_at', $direction)
                ),
            Column::make('Actions')
                ->label(
                    fn ($row) => view('admin.users.users.datatable_actions')->with(['row' => $row])
                ),
        ];
    }

    public function builder(): Builder
    {
        return User::query()->select('users.*');
    }

    public function filters(): array
    {
        return [
            'verified' => SelectFilter::make('E-mail verified')
                ->options([
                    ''    => 'Any',
                    'yes' => 'Yes',
                    'no'  => 'No',
                ])
                ->filter(fn ($query, $term) => $term === 'yes' ? $query->whereNotNull('email_verified_at') : $query->whereNull('email_verified_at')),
            'admin'    => SelectFilter::make('Is Admin')
                ->options([
                    ''    => 'Any',
                    'yes' => 'Yes',
                    'no'  => 'No',
                ])
                ->filter(fn ($query, $term) => $query->where('permission', '=', $term === 'yes' ? User::PERMISSION_ADMIN : User::PERMISSION_USER)),
        ];
    }
}
