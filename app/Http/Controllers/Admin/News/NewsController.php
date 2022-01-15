<?php

namespace App\Http\Controllers\Admin\News;

use App\Helpers\ChangelogHelper;
use App\Http\Controllers\Controller;
use App\Models\Changelog;
use App\Models\News;
use App\Models\User;
use App\View\Components\Admin\Crumb;
use Carbon\Carbon;
use Illuminate\Http\Request;

class NewsController extends Controller
{
    public function index()
    {
        return view('admin.news.news.index')
            ->with([
                'breadcrumbs' => [
                    new Crumb(route('admin.news.news.index'), 'News'),
                ],
            ]);
    }

    public function edit(News $news)
    {
        return view('admin.news.news.edit')
            ->with([
                'breadcrumbs' => [
                    new Crumb(route('admin.news.news.index'), 'News'),
                    new Crumb(route('admin.news.news.edit', $news), $news->news_headline),
                ],
                'news'        => $news,
            ]);
    }

    public function create()
    {
        return view('admin.news.news.edit')
            ->with([
                'breadcrumbs' => [
                    new Crumb(route('admin.news.news.index'), 'News'),
                    new Crumb(route('admin.news.news.create'), 'Create'),
                ],
            ]);
    }

    public function update(Request $request, News $news)
    {
        $request->validate([
            'headline' => 'required',
            'author'   => 'required|numeric',
            'date'     => 'required|date',
            'text'     => 'required',
        ]);

        $news->update([
            'news_headline' => $request->headline,
            'user_id'       => User::find($request->author)->user_id,
            'news_date'     => Carbon::parse($request->date)->timestamp,
            'news_text'     => $request->text,
        ]);

        // FIXME: image

        ChangelogHelper::insert([
            'action'           => Changelog::UPDATE,
            'section'          => 'News',
            'section_id'       => $news->getKey(),
            'section_name'     => $news->news_headline,
            'sub_section'      => 'News item',
            'sub_section_id'   => $news->getKey(),
            'sub_section_name' => $news->news_headline,
        ]);

        return redirect()->route('admin.news.news.index');
    }

    public function destroy(News $news)
    {
        // CHANGELOG

        return redirect()->route('admin.news.news.index');
    }
}
