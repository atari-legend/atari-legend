<?php

namespace App\Http\Controllers\Admin\News;

use App\Helpers\ChangelogHelper;
use App\Http\Controllers\Controller;
use App\Models\Changelog;
use App\Models\News;
use App\Models\NewsSubmission;
use App\View\Components\Admin\Crumb;

class NewsSubmissionsController extends Controller
{
    public function index()
    {
        return view('admin.news.submissions.index')
            ->with([
                'breadcrumbs' => [
                    new Crumb(route('admin.news.submissions.index'), 'News submissions'),
                ],
            ]);
    }

    public function approve(NewsSubmission $submission)
    {
        $news = News::create([
            'news_headline' => $submission->news_headline,
            'user_id'       => $submission->user_id,
            'news_date'     => $submission->news_date->timestamp,
            'news_text'     => $submission->news_text,
        ]);

        ChangelogHelper::insert([
            'action'           => Changelog::UPDATE,
            'section'          => 'News',
            'section_id'       => $news->getKey(),
            'section_name'     => $news->news_headline,
            'sub_section'      => 'News item',
            'sub_section_id'   => $news->getKey(),
            'sub_section_name' => $news->news_headline,
        ]);

        $this->destroy($submission);

        return redirect()->route('admin.news.news.edit', $news);
    }

    public function destroy(NewsSubmission $submission)
    {
        $submission->delete();

        ChangelogHelper::insert([
            'action'           => Changelog::DELETE,
            'section'          => 'News',
            'section_id'       => $submission->getKey(),
            'section_name'     => $submission->news_headline,
            'sub_section'      => 'News submit',
            'sub_section_id'   => $submission->getKey(),
            'sub_section_name' => $submission->news_headline,
        ]);

        return redirect()->route('admin.news.submissions.index');
    }
}
