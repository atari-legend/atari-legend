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
            'headline' => $submission->headline,
            'user_id'  => $submission->user_id,
            'date'     => $submission->date->timestamp,
            'text'     => $submission->text,
        ]);

        ChangelogHelper::insert([
            'action'           => Changelog::UPDATE,
            'section'          => 'News',
            'section_id'       => $news->getKey(),
            'section_name'     => $news->headline,
            'sub_section'      => 'News item',
            'sub_section_id'   => $news->getKey(),
            'sub_section_name' => $news->headline,
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
            'section_name'     => $submission->headline,
            'sub_section'      => 'News submit',
            'sub_section_id'   => $submission->getKey(),
            'sub_section_name' => $submission->headline,
        ]);

        return redirect()->route('admin.news.submissions.index');
    }
}
