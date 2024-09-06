<?php

namespace App\Http\Livewire\Admin;

use App\Models\Spotlight;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Columns\LinkColumn;

class SpotlightsTable extends DataTableComponent
{
    public function configure(): void
    {
        $this->setPrimaryKey('spotlight_id');
        $this->setDefaultSort('spotlight');
    }

    public function columns(): array
    {
        return [
            LinkColumn::make('Spotlight')
                ->title(fn($row) => Str::words($row->spotlight, 15))
                ->location(fn($row) => route('admin.others.spotlights.edit', $row))
                ->searchable(
                    fn(Builder $query, string $term) => $query->where('spotlight', 'like', "%{$term}%")
                        ->orWhere('link', 'like', "%{$term}%")
                )
                ->sortable(fn(Builder $query, string $direction) => $query->orderBy('spotlight', $direction)),
            Column::make('Link', 'link')
                ->sortable(),
            Column::make('Actions')
                ->label(
                    fn($row) => view('admin.others.spotlights.datatable_actions')->with(['row' => $row])
                ),
        ];
    }

    public function builder(): Builder
    {
        return Spotlight::select();
    }
}
