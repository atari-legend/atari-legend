<?php

namespace App\View\Components\Cards;

use App\Models\GameComment;
use App\Models\User;
use Illuminate\View\Component;

class LatestComments extends Component
{
    /**
     * Optional user to get the comments for.
     */
    public ?User $user = null;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(?User $user)
    {
        // Not sure why but when no $user is passed to the component,
        // Laravel provides a newly created user rather than NULL
        // So we check the user_id to see if it's a real user, or a newly
        // created one
        if ($user !== null && $user->getKey() !== null) {
            $this->user = $user;
        }
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\View\View|string
     */
    public function render()
    {
        $comments = GameComment::query();

        if ($this->user !== null) {
            $comments->where('user_id', $this->user->getKey());
        }

        $comments = $comments->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('components.cards.latest-comments')
            ->with(['comments' => $comments]);
    }
}
