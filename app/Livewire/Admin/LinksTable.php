<?php

namespace App\Livewire\Admin;

use App\Models\WebsiteCategory;
use App\Models\Website;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Columns\LinkColumn;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

class LinksTable extends DataTableComponent
{
    public function configure(): void
    {
        $this->setPrimaryKey('website_id');
        $this->setDefaultSort('website_name');
    }

    public function columns(): array
    {
        return [
            LinkColumn::make('Name', 'website_name')
                ->title(fn ($row) => $row->website_name)
                ->location(fn ($row) => route('admin.links.links.edit', $row))
                ->searchable(
                    fn (Builder $query, string $term) => $query->where('website.website_name', 'like', "%{$term}%")
                        ->orWhere('website.website_url', 'like', "%{$term}%")
                        ->orWhere('website.description', 'like', "%{$term}%")
                )
                ->sortable(fn (Builder $query, string $direction) => $query->orderBy('website_name', $direction)),
            Column::make('URL', 'website_url')
                ->format(fn ($value) => '<a href="' . e($value) . '" target="_blank" rel="noopener noreferrer">' . e($value) . '</a>')
                ->html()
                ->sortable(),
            Column::make('Categories')
                ->label(fn ($row) => $row->categories->pluck('website_category_name')->join(', ')),
            Column::make('Inactive', 'inactive')
                ->format(fn ($value) => $value ? '<i class="fas fa-exclamation-triangle text-warning" title="Inactive" aria-label="Inactive"></i>' : '')
                ->html()
                ->sortable(),
            Column::make('Actions')
                ->label(
                    fn ($row) => view('admin.links.links.datatable_actions')->with(['row' => $row])
                ),
        ];
    }

    public function builder(): Builder
    {
        return Website::with('categories')->select('website.*');
    }

    public function filters(): array
    {
        $categories = WebsiteCategory::orderBy('website_category_name')
            ->get()
            ->mapWithKeys(fn ($cat) => [strval($cat->website_category_id) => $cat->website_category_name])
            ->all();
        $categories = ['' => 'Any'] + $categories;

        return [
            'category' => SelectFilter::make('Category')
                ->options($categories)
                ->filter(function (Builder $query, string $value) {
                    $query->whereHas('categories', fn ($q) => $q->where('website_category.website_category_id', $value));
                }),
            'inactive' => SelectFilter::make('Status')
                ->options(['' => 'Any', '0' => 'Active', '1' => 'Inactive'])
                ->filter(fn (Builder $query, string $value) => $query->where('website.inactive', $value)),
        ];
    }
}
