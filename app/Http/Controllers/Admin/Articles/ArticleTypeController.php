<?php

namespace App\Http\Controllers\Admin\Articles;

use App\Helpers\ChangelogHelper;
use App\Http\Controllers\Controller;
use App\Models\ArticleType;
use App\Models\Changelog;
use App\View\Components\Admin\Crumb;
use Illuminate\Http\Request;

class ArticleTypeController extends Controller
{
    public function index()
    {
        $types = ArticleType::orderBy('article_type')
            ->get();

        return view('admin.articles.types.index')
            ->with([
                'breadcrumbs' => [
                    new Crumb(route('admin.articles.types.index'), 'Article types'),
                ],
                'types'       => $types,
            ]);
    }

    public function store(Request $request)
    {
        $request->validate(['type' => 'required']);

        $type = ArticleType::create(['article_type' => $request->type]);

        ChangelogHelper::insert([
            'action'           => Changelog::INSERT,
            'section'          => 'Article type',
            'section_id'       => $type->getKey(),
            'section_name'     => $type->article_type,
            'sub_section'      => 'Article type',
            'sub_section_id'   => $type->getKey(),
            'sub_section_name' => $type->article_type,
        ]);

        return redirect()->route('admin.articles.types.index');
    }

    public function update(Request $request, ArticleType $type)
    {
        $request->validate(['type' => 'required']);
        $type->update(['article_type' => $request->type]);

        ChangelogHelper::insert([
            'action'           => Changelog::UPDATE,
            'section'          => 'Article type',
            'section_id'       => $type->getKey(),
            'section_name'     => $type->article_type,
            'sub_section'      => 'Article type',
            'sub_section_id'   => $type->getKey(),
            'sub_section_name' => $type->article_type,
        ]);

        return redirect()->route('admin.articles.types.index');
    }

    public function destroy(ArticleType $type)
    {
        $type->delete();

        ChangelogHelper::insert([
            'action'           => Changelog::DELETE,
            'section'          => 'Article type',
            'section_id'       => $type->getKey(),
            'section_name'     => $type->article_type,
            'sub_section'      => 'Article type',
            'sub_section_id'   => $type->getKey(),
            'sub_section_name' => $type->article_type,
        ]);

        return redirect()->route('admin.articles.types.index');
    }
}
