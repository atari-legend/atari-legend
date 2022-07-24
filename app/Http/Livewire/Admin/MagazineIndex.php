<?php

namespace App\Http\Livewire\Admin;

use App\Models\Game;
use App\Models\MagazineIndex as ModelsMagazineIndex;
use App\Models\MagazineIndexType;
use App\Models\MagazineIssue;
use App\Models\MenuSoftware;
use Livewire\Component;

class MagazineIndex extends Component
{
    public MagazineIssue $issue;

    public $sort = false;

    protected $rules = [
        'sort'                                   => 'boolean',
        'issue.indexes.*.page'                   => 'numeric',
        'issue.indexes.*.title'                  => 'string',
        'issue.indexes.*.score'                  => 'string',
        'issue.indexes.*.game_id'                => 'numeric',
        'issue.indexes.*.menu_software_id'       => 'numeric',
        'issue.indexes.*.magazine_index_type_id' => 'numeric',
    ];

    public function addRow()
    {
        $this->issue->indexes()->save(new ModelsMagazineIndex(['game_id' => null]));
        $this->issue->refresh();
    }

    public function deleteRow(ModelsMagazineIndex $index)
    {
        $index->delete();
        $this->issue->refresh();
    }

    public function updateSoftware(ModelsMagazineIndex $index, int $softwareId)
    {
        $index->menuSoftware()->associate(MenuSoftware::find($softwareId));
        $index->save();
        $this->issue->refresh();
    }

    public function updateGame(ModelsMagazineIndex $index, int $gameId)
    {
        $index->game()->associate(Game::find($gameId));
        $index->save();
        $this->issue->refresh();
    }

    public function save()
    {
        $this->issue->indexes->each(function ($index) {
            $index->save();
        });
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.admin.magazine-index')
            ->with([
                'indices'    => $this->sort ? $this->issue->indexes->sortBy('page') : $this->issue->indexes,
                'indexTypes' => MagazineIndexType::all(),
            ]);
    }
}
