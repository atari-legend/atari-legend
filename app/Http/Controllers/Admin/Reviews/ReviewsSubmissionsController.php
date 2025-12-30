<?php

namespace App\Http\Controllers\Admin\Reviews;

use App\Http\Controllers\Controller;
use App\View\Components\Admin\Crumb;

class ReviewsSubmissionsController extends Controller
{
    public function index()
    {
        return view('admin.reviews.submissions.index')
            ->with([
                'breadcrumbs' => [
                    new Crumb(route('admin.reviews.submissions.index'), 'Submissions'),
                ],
            ]);
    }
}
