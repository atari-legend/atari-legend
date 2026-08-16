<?php

namespace App\Livewire\Admin;

use App\Helpers\ChangelogHelper;
use App\Models\Changelog;
use App\Models\Game;
use App\Models\Individual;
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
        'issue.indices.*.page'                   => 'nullable|numeric',
        'issue.indices.*.title'                  => 'nullable|string',
        'issue.indices.*.score'                  => 'nullable|string',
        'issue.indices.*.game_id'                => 'nullable|numeric',
        'issue.indices.*.menu_software_id'       => 'nullable|numeric',
        'issue.indices.*.magazine_index_type_id' => 'nullable|numeric',
    ];

    public function addRow()
    {
        $this->save();
        $this->issue->indices()->save(new ModelsMagazineIndex(['game_id' => null]));
        $this->issue->refresh();
        $this->dispatch('magazine-index-rows-updated');
    }

    public function deleteRow(ModelsMagazineIndex $index)
    {
        $index->delete();
        $this->issue->refresh();
        $this->dispatch('magazine-index-rows-updated');
    }

    public function updateSoftware(int $indexId, ?int $softwareId)
    {
        $this->validate();

        $index = $this->issue->indices->firstWhere('id', $indexId);
        if ($softwareId != null) {
            $index->menuSoftware()->associate(MenuSoftware::find($softwareId));
        } else {
            $index->menuSoftware()->dissociate();
        }
        MagazineIndex::fixAttributes($index);
        $index->save();
        $this->issue->refresh();
    }

    public function updateGame(int $indexId, ?int $gameId)
    {
        $this->validate();

        $index = $this->issue->indices->firstWhere('id', $indexId);
        if ($gameId != null) {
            $index->game()->associate(Game::find($gameId));
        } else {
            $index->game()->dissociate();
        }
        MagazineIndex::fixAttributes($index);
        $index->save();
        $this->issue->refresh();
    }

    public function updateIndividual(int $indexId, ?int $individualId)
    {
        $this->validate();

        $index = $this->issue->indices->firstWhere('id', $indexId);
        if ($individualId != null) {
            $index->individual()->associate(Individual::find($individualId));
        } else {
            $index->individual()->dissociate();
        }
        MagazineIndex::fixAttributes($index);
        $index->save();
        $this->issue->refresh();
    }

    public function save()
    {
        $this->validate();

        if ($this->issue->indices) {
            $this->issue->indices->each(function ($index) {
                MagazineIndex::fixAttributes($index);
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
                'indices'    => $this->sort ? $this->issue->indices->sortBy(['page', 'game.game_name']) : $this->issue->indices,
                'indexTypes' => MagazineIndexType::all(),
            ]);
    }

    /**
     * Fix the attributes of an index the UI can leave in a state the database
     * will not take.
     *
     * The page is a free text field, so it can hold anything. The type is a
     * select whose blank option has no value of its own, and an empty string
     * is not something an integer column accepts - MySQL rejects the UPDATE
     * outright, and nothing on the screen says so.
     *
     * @param  MagazineIndex  $index  Index to fix the attributes of.
     */
    private static function fixAttributes(ModelsMagazineIndex &$index)
    {
        if ($index->page !== null && ! is_numeric($index->page)) {
            $index->page = null;
        }

        if ('' === $index->magazine_index_type_id) {
            $index->magazine_index_type_id = null;
        }
    }
}
