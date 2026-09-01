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
        $news = News::select('news.*')
            ->orderByDesc('date')
            ->paginate(6);

        return view('news.index')
            ->with([
                'news'  => $news,
            ]);
    }

    public function postNews(Request $request)
    {
        $submission = new NewsSubmission();
        $submission->headline = $request->title;
        $submission->text = $request->text;
        $submission->date = time();

        $request->user()->newsSubmissions()->save($submission);

        ChangelogHelper::insert([
            'action'           => Changelog::INSERT,
            'section'          => 'News',
            'section_id'       => $submission->getKey(),
            'section_name'     => $submission->headline,
            'sub_section'      => 'News submit',
            'sub_section_id'   => $submission->getKey(),
            'sub_section_name' => $submission->headline,
        ]);

        $request->session()->flash('alert-title', 'News submitted');
        $request->session()->flash(
            'alert-success',
            'Thanks for your submission, a moderator will review it soon!'
        );

        return back();
    }
}
