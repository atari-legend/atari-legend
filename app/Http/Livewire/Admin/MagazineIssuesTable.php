<?php

namespace App\Http\Livewire\Admin;

use App\Models\MagazineIssue;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class MagazineIssuesTable extends DataTableComponent
{
    public bool $showSearch = false;
    public array $perPageAccepted = [25, 50, 100];
    public bool $perPageAll = true;

    public $magazine = 0;

    public string $defaultSortColumn = 'issue';
    public string $defaultSortDirection = 'asc';

    public function columns(): array
    {
        return [
            Column::make('Issue', 'issue')->sortable(),
            Column::make('Created', 'created_at')->sortable(),
            Column::make('Updated', 'updated_at')->sortable(),
            Column::blank(),
        ];
    }

    public function query(): Builder
    {
        return MagazineIssue::where('magazine_id', '=', $this->magazine);
    }

    public function filters(): array
    {
        return [];
    }

    public function getTableRowUrl($row): string
    {
        return route('admin.magazines.issues.edit', $row);
    }

    public function rowView(): string
    {
        return 'admin.magazines.issues.list_row';
    }
}
