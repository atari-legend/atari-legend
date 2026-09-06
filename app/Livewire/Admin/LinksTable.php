<?php

namespace App\Livewire\Admin;

use App\Models\Category;
use App\Models\Link;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Columns\LinkColumn;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

class LinksTable extends DataTableComponent
{
    public function configure(): void
    {
        $this->setPrimaryKey('id');
        $this->setDefaultSort('name');
    }

    public function columns(): array
    {
        return [
            LinkColumn::make('Name', 'name')
                ->title(fn ($row) => $row->name)
                ->location(fn ($row) => route('admin.links.links.edit', $row))
                ->searchable(
                    fn (Builder $query, string $term) => $query->where('links.name', 'like', "%{$term}%")
                        ->orWhere('links.url', 'like', "%{$term}%")
                        ->orWhere('links.description', 'like', "%{$term}%")
                )
                ->sortable(fn (Builder $query, string $direction) => $query->orderBy('links.name', $direction)),
            Column::make('URL', 'url')
                ->format(fn ($value) => '<a href="' . e($value) . '" target="_blank" rel="noopener noreferrer">' . e($value) . '</a>')
                ->html()
                ->sortable(),
            Column::make('Categories')
                ->label(fn ($row) => $row->categories->pluck('name')->join(', ')),
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
        return Link::with('categories')->select('links.*');
    }

    public function filters(): array
    {
        $categories = Category::orderBy('name')
            ->get()
            ->mapWithKeys(fn ($cat) => [strval($cat->getKey()) => $cat->name])
            ->all();
        $categories = ['' => 'Any'] + $categories;

        return [
            'category' => SelectFilter::make('Category')
                ->options($categories)
                ->filter(function (Builder $query, string $value) {
                    $query->whereHas('categories', fn ($q) => $q->where('categories.id', $value));
                }),
            'inactive' => SelectFilter::make('Status')
                ->options(['' => 'Any', '0' => 'Active', '1' => 'Inactive'])
                ->filter(fn (Builder $query, string $value) => $query->where('links.inactive', $value)),
        ];
    }
}
