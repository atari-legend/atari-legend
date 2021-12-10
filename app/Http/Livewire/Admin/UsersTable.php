<?php

namespace App\Http\Livewire\Admin;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filter;

class UsersTable extends DataTableComponent
{
    public string $primaryKey = 'user_id';

    public function columns(): array
    {
        return [
            Column::make('Name', 'userid')->sortable(),
            Column::make('Avatar')
                ->sortable(function (Builder $query, $direction) {
                    return $query->orderBy('avatar_ext', $direction);
                }),
            Column::make('Join date')
                ->sortable(function (Builder $query, $direction) {
                    // Validate direction to avoid SQL injections
                    $d = $direction === 'asc' ? 'asc' : 'desc';

                    return $query->orderByRaw("convert(join_date, unsigned) $d");
                }),
            Column::make('Last visit')
                ->sortable(function (Builder $query, $direction) {
                    // Validate direction to avoid SQL injections
                    $d = $direction === 'asc' ? 'asc' : 'desc';

                    return $query->orderByRaw("convert(last_visit, unsigned) $d");
                }),
            Column::blank(),
        ];
    }

    public function query(): Builder
    {
        return User::query()
            ->when(
                $this->getFilter('search'),
                fn ($query, $term) => $query->where('userid', 'like', '%'.$term.'%')
            )
            ->when(
                $this->getFilter('verified'),
                fn ($query, $term) => $term === 'yes' ? $query->whereNotNull('email_verified_at') : $query->whereNull('email_verified_at')
            )
            ->when(
                $this->getFilter('admin'),
                fn ($query, $term) => $query->where('permission', '=', $term === 'yes' ? User::PERMISSION_ADMIN : User::PERMISSION_USER)
            );
    }

    public function filters(): array
    {
        return [
            'verified' => Filter::make('E-mail verified')
                ->select([
                    ''    => 'Any',
                    'yes' => 'Yes',
                    'no'  => 'No',
                ]),
            'admin'    => Filter::make('Is Admin')
                ->select([
                    ''    => 'Any',
                    'yes' => 'Yes',
                    'no'  => 'No',
                ]),
        ];
    }

    public function getTableRowUrl($row): string
    {
        return route('admin.users.users.edit', $row);
    }

    public function rowView(): string
    {
        return 'admin.users.users.list_row';
    }
}
