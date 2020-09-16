<?php

namespace App\View\Components\Cards;

use Illuminate\View\Component;

class LatestComments extends Component
{
    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\View\View|string
     */
    public function render()
    {
        $comments = \App\Comment::select()
            ->join('game_user_comments', 'comments_id', '=', 'game_user_comments.comment_id')
            ->orderBy('timestamp', 'desc')
            ->limit(10)
            ->get();

        return view('components.cards.latest-comments')
            ->with(['comments' => $comments]);
    }
}
