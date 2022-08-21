<?php

namespace App\Http\Livewire\Admin;

use App\Helpers\ChangelogHelper;
use App\Models\Changelog;
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
        'issue.indices.*.page'                   => 'numeric',
        'issue.indices.*.title'                  => 'string',
        'issue.indices.*.score'                  => 'string',
        'issue.indices.*.game_id'                => 'numeric',
        'issue.indices.*.menu_software_id'       => 'numeric',
        'issue.indices.*.magazine_index_type_id' => 'numeric',
    ];

    public function addRow()
    {
        $this->save();
        $this->issue->indices()->save(new ModelsMagazineIndex(['game_id' => null]));
        $this->issue->refresh();
    }

    public function deleteRow(ModelsMagazineIndex $index)
    {
        $index->delete();
        $this->issue->refresh();
    }

    public function updateSoftware(int $indexId, ?int $softwareId)
    {
        $index = $this->issue->indices->firstWhere('id', $indexId);
        if ($softwareId != null) {
            $index->menuSoftware()->associate(MenuSoftware::find($softwareId));
        } else {
            $index->menuSoftware()->dissociate();
        }
        $index->save();
        $this->issue->refresh();
    }

    public function updateGame(int $indexId, ?int $gameId)
    {
        $index = $this->issue->indices->firstWhere('id', $indexId);
        if ($gameId != null) {
            $index->game()->associate(Game::find($gameId));
        } else {
            $index->game()->dissociate();
        }
        $index->save();
        $this->issue->refresh();
    }

    public function save()
    {
        if ($this->issue->indices) {
            $this->issue->indices->each(function ($index) {
                $index->save();
            });
        }

        ChangelogHelper::insert([
            'action'           => Changelog::UPDATE,
            'section'          => 'Magazines',
            'section_id'       => $this->issue->magazine->getKey(),
            'section_name'     => $this->issue->magazine->name,
            'sub_section'      => 'Index',
            'sub_section_id'   => $this->issue->getKey(),
            'sub_section_name' => $this->issue->issue,
        ]);
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
                'indices'    => $this->sort ? $this->issue->indices->sortBy('page') : $this->issue->indices,
                'indexTypes' => MagazineIndexType::all(),
            ]);
    }
}
