<?php

namespace App\Http\Controllers\Admin\Other;

use App\Helpers\ChangelogHelper;
use App\Http\Controllers\Controller;
use App\Models\Changelog;
use App\Models\Trivia;
use App\View\Components\Admin\Crumb;
use Illuminate\Http\Request;

class TriviaController extends Controller
{
    public function index()
    {
        $trivias = Trivia::orderByDesc('trivia_id')
            ->get();

        return view('admin.others.trivias.index')
            ->with([
                'breadcrumbs' => [
                    new Crumb(route('admin.others.trivias.index'), 'Did you know?'),
                ],
                'trivias'     => $trivias,
            ]);
    }

    public function update(Request $request, Trivia $trivia)
    {
        $request->validate([
            'text' => 'required',
        ]);

        $oldText = $trivia->trivia_text;

        $trivia->trivia_text = $request->text;
        $trivia->save();

        ChangelogHelper::insert([
            'action'           => Changelog::UPDATE,
            'section'          => 'Trivia',
            'section_id'       => $trivia->getKey(),
            'section_name'     => $oldText,
            'sub_section'      => 'DYK',
            'sub_section_id'   => $trivia->getKey(),
            'sub_section_name' => $trivia->trivia_text,
        ]);

        return redirect()->route('admin.others.trivias.index');
    }

    public function store(Request $request)
    {
        $request->validate([
            'text' => 'required',
        ]);

        $trivia = Trivia::create([
            'trivia_text' => $request->text,
        ]);

        ChangelogHelper::insert([
            'action'           => Changelog::INSERT,
            'section'          => 'Trivia',
            'section_id'       => $trivia->getKey(),
            'section_name'     => $trivia->trivia_text,
            'sub_section'      => 'DYK',
            'sub_section_id'   => $trivia->getKey(),
            'sub_section_name' => $trivia->trivia_text,
        ]);

        return redirect()->route('admin.others.trivias.index');
    }

    public function destroy(Trivia $trivia)
    {
        $trivia->delete();

        ChangelogHelper::insert([
            'action'           => Changelog::DELETE,
            'section'          => 'Trivia',
            'section_id'       => $trivia->getKey(),
            'section_name'     => $trivia->trivia_text,
            'sub_section'      => 'DYK',
            'sub_section_id'   => $trivia->getKey(),
            'sub_section_name' => $trivia->trivia_text,
        ]);

        return redirect()->route('admin.others.trivias.index');
    }
}
