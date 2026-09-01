<?php

namespace App\Http\Controllers\Admin\News;

use App\Helpers\ChangelogHelper;
use App\Http\Controllers\Controller;
use App\Models\Changelog;
use App\Models\News;
use App\Models\NewsImage;
use App\Models\User;
use App\View\Components\Admin\Crumb;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class NewsController extends Controller
{
    const VALIDATION_RULES = [
        'headline' => 'required',
        'author'   => 'required|numeric',
        'date'     => 'required|date',
        'text'     => 'required',
    ];

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
                    new Crumb(route('admin.news.news.edit', $news), $news->headline),
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
        $request->validate(NewsController::VALIDATION_RULES);

        $news->update([
            'headline' => $request->headline,
            'user_id'       => User::find($request->author)->getKey(),
            'date'     => Carbon::parse($request->date)->timestamp,
            'text'     => $request->text,
        ]);

        $this->addOrUpdateImage($request, $news);

        ChangelogHelper::insert([
            'action'           => Changelog::UPDATE,
            'section'          => 'News',
            'section_id'       => $news->getKey(),
            'section_name'     => $news->headline,
            'sub_section'      => 'News item',
            'sub_section_id'   => $news->getKey(),
            'sub_section_name' => $news->headline,
        ]);

        return redirect()->route('admin.news.news.index');
    }

    public function store(Request $request)
    {
        $request->validate(NewsController::VALIDATION_RULES);

        $news = News::create([
            'headline' => $request->headline,
            'user_id'       => User::find($request->author)->getKey(),
            'date'     => Carbon::parse($request->date)->timestamp,
            'text'     => $request->text,
        ]);

        $this->addOrUpdateImage($request, $news);

        ChangelogHelper::insert([
            'action'           => Changelog::INSERT,
            'section'          => 'News',
            'section_id'       => $news->getKey(),
            'section_name'     => $news->headline,
            'sub_section'      => 'News item',
            'sub_section_id'   => $news->getKey(),
            'sub_section_name' => $news->headline,
        ]);

        return redirect()->route('admin.news.news.index');
    }

    public function destroy(News $news)
    {
        $this->destroyImage($news);
        $news->delete();

        ChangelogHelper::insert([
            'action'           => Changelog::DELETE,
            'section'          => 'News',
            'section_id'       => $news->getKey(),
            'section_name'     => $news->headline,
            'sub_section'      => 'News item',
            'sub_section_id'   => $news->getKey(),
            'sub_section_name' => $news->headline,
        ]);

        return redirect()->route('admin.news.news.index');
    }

    public function destroyImage(News $news)
    {
        if ($news->news_image) {
            Storage::disk('public')->delete('images/news_images/' . $news->image->file);
            $news->image->delete();

            // And let go of it: deleting the row on its own left
            // news.news_image_id pointing at an id that is no longer there.
            $news->news_image_id = null;
            $news->save();

            ChangelogHelper::insert([
                'action'           => Changelog::DELETE,
                'section'          => 'News',
                'section_id'       => $news->getKey(),
                'section_name'     => $news->headline,
                'sub_section'      => 'Image',
                'sub_section_id'   => $news->getKey(),
                'sub_section_name' => $news->headline,
            ]);
        }

        return redirect()->route('admin.news.news.edit', $news);
    }

    private function addOrUpdateImage(Request $request, News $news)
    {
        if ($request->hasFile('image')) {
            $newsImage = $news->image;
            $action = Changelog::UPDATE;

            if (! $newsImage) {
                $newsImage = new NewsImage();
                $newsImage->save();
                $news->image()->associate($newsImage);
                $news->save();

                $action = Changelog::INSERT;
            }

            $image = $request->file('image');
            $image->storeAs('images/news_images/', $newsImage->getKey() . '.' . $image->extension(), 'public');

            $newsImage->update(['imgext' => $image->extension()]);

            ChangelogHelper::insert([
                'action'           => $action,
                'section'          => 'News',
                'section_id'       => $news->getKey(),
                'section_name'     => $news->headline,
                'sub_section'      => 'Image',
                'sub_section_id'   => $news->getKey(),
                'sub_section_name' => $news->headline,
            ]);
        }
    }
}
