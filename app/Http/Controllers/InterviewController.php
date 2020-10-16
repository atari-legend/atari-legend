<?php

namespace App\Http\Controllers;

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

        return view('interviews.show')
            ->with([
                'interview'       => $interview,
                'interviews'      => $interviews,
            ]);
    }

    public function postComment(Interview $interview, Request $request)
    {
        $comment = new Comment();
        $comment->comment = $request->comment;
        $comment->timestamp = time();

        $request->user()->comments()->save($comment);
        $interview->comments()->save($comment);

        return back();
    }
}
