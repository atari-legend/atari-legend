<?php

namespace App\Http\Controllers;

use App\Helpers\ChangelogHelper;
use App\Helpers\Helper;
use App\Helpers\JsonLd;
use App\Models\Changelog;
use App\Models\Comment;
use App\Models\Interview;
use Illuminate\Http\Request;

class InterviewController extends Controller
{
    public function index()
    {
        $interviews = Interview::select()
            ->join('interview_text', 'interview_text.interview_id', '=', 'interview_main.interview_id')
            ->orderByDesc('interview_text.interview_date')
            ->paginate(5);

        return view('interviews.index')
            ->with(['interviews' => $interviews]);
    }

    public function show(Interview $interview)
    {
        $interviews = Interview::select()
            ->join('interview_text', 'interview_text.interview_id', '=', 'interview_main.interview_id')
            ->orderByDesc('interview_text.interview_date')
            ->limit(5)
            ->get();

        $jsonLd = (new JsonLd('Article', url()->current()))
            ->add('headline', 'Interview of '.$interview->individual->ind_name)
            ->add('author', Helper::user($interview->user))
            ->add('datePublished', $interview->texts->first()->interview_date->format('Y-m-d'));
        if ($interview->individual?->text?->file !== null) {
            $jsonLd->add('image', asset('storage/images/individual_screenshots/'.$interview->individual->text->file));
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
        $comment = new Comment();
        $comment->comment = $request->comment;
        $comment->timestamp = time();

        $request->user()->comments()->save($comment);
        $interview->comments()->save($comment);

        ChangelogHelper::insert([
            'action'           => Changelog::INSERT,
            'section'          => 'Interviews',
            'section_id'       => $interview->getKey(),
            'section_name'     => $interview->individual->ind_name,
            'sub_section'      => 'Comment',
            'sub_section_id'   => $interview->individual->getKey(),
            'sub_section_name' => $interview->individual->ind_name,
        ]);

        return back();
    }
}
