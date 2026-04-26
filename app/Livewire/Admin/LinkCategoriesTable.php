<?php

namespace App\Livewire\Admin;

use App\Models\WebsiteCategory;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Columns\LinkColumn;

class LinkCategoriesTable extends DataTableComponent
{
    public function configure(): void
    {
        $this->setPrimaryKey('website_category_id');
        $this->setDefaultSort('website_category_name');
    }

    public function columns(): array
    {
        return [
            LinkColumn::make('Name', 'website_category_name')
                ->title(fn ($row) => $row->website_category_name)
                ->location(fn ($row) => route('admin.links.categories.edit', $row))
                ->searchable(
                    fn (Builder $query, string $term) => $query->where('website_category_name', 'like', "%{$term}%")
                )
                ->sortable(),
            Column::make('Links')
                ->label(fn ($row) => $row->websites_count),
            Column::make('Actions')
                ->label(
                    fn ($row) => view('admin.links.categories.datatable_actions')->with(['row' => $row])
                ),
        ];
    }

    public function builder(): Builder
    {
        return WebsiteCategory::select('website_category.*')->withCount('websites');
    }
}
