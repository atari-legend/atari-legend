<?php

namespace App\Http\Controllers;

use App\Interview;

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
}
