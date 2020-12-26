<?php

namespace App\Http\Controllers;

use App\Helpers\ChangelogHelper;
use App\Models\Changelog;
use App\Models\News;
use App\Models\NewsSubmission;
use Illuminate\Http\Request;

class NewsController extends Controller
{
    public function index()
    {
        $news = News::select()
            ->orderByDesc('news_date')
            ->paginate(6);

        return view('news.index')
            ->with([
                'news'  => $news,
            ]);
    }

    public function postNews(Request $request)
    {
        $submission = new NewsSubmission();
        $submission->news_headline = $request->title;
        $submission->news_text = $request->text;
        $submission->news_date = time();

        $request->user()->newsSubmissions()->save($submission);

        ChangelogHelper::insert([
            'action'           => Changelog::INSERT,
            'section'          => 'News',
            'section_id'       => $submission->getKey(),
            'section_name'     => $submission->news_headline,
            'sub_section'      => 'News submit',
            'sub_section_id'   => $submission->getKey(),
            'sub_section_name' => $submission->news_headline,
        ]);

        $request->session()->flash('alert-title', 'News submitted');
        $request->session()->flash(
            'alert-success',
            'Thanks for your submission, a moderator will review it soon!'
        );

        return back();
    }
}
