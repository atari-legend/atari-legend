<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameSubmission extends Model
{
    const UPDATED_AT = null;

    protected $casts = [
        'reviewed' => 'boolean',
    ];

    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    public function screenshots()
    {
        return $this->belongsToMany(Screenshot::class);
    }

    public function user()
    {
        // No third argument: the owner key on User is now `id`, the default.
        return $this->belongsTo(User::class);
    }
}
