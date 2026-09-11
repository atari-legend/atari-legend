<?php

namespace App\Http\Controllers;

use App\Helpers\ChangelogHelper;
use App\Helpers\Helper;
use App\Helpers\JsonLd;
use App\Models\Changelog;
use App\Models\Interview;
use App\Models\InterviewComment;
use Illuminate\Http\Request;

class InterviewController extends Controller
{
    public function index()
    {
        $interviews = Interview::orderByDesc('published_at')
            ->paginate(5);

        return view('interviews.index')
            ->with(['interviews' => $interviews]);
    }

    public function show(Interview $interview)
    {
        $interviews = Interview::orderByDesc('published_at')
            ->limit(5)
            ->get();

        $jsonLd = (new JsonLd('Article', url()->current()))
            ->add('headline', 'Interview of ' . $interview->individual->name)
            ->add('author', Helper::user($interview->user))
            ->add('datePublished', $interview->published_at->format('Y-m-d'));
        if ($interview->individual?->file !== null) {
            $jsonLd->add('image', route('individuals.avatar', $interview->individual));
        }

        return view('interviews.show')
            ->with([
                'interview'       => $interview,
                'interviews'      => $interviews,
                'jsonLd'          => $jsonLd,
            ]);
    }

    public function postComment(Interview $interview, Request $request)
    {
        $comment = new InterviewComment();
        $comment->text = $request->comment;

        $interview->comments()->save($comment);
        $request->user()->interviewComments()->save($comment);

        ChangelogHelper::insert([
            'action'           => Changelog::INSERT,
            'section'          => 'Interviews',
            'section_id'       => $interview->getKey(),
            'section_name'     => $interview->individual->name,
            'sub_section'      => 'Comment',
            'sub_section_id'   => $interview->individual->getKey(),
            'sub_section_name' => $interview->individual->name,
        ]);

        return back();
    }
}
