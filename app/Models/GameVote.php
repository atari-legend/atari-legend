<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GameVote extends Model
{
    use HasFactory;

    const LABELS = [
        0 => 'Terrible',
        1 => 'Bad',
        2 => 'Average',
        3 => 'Good',
        4 => 'Awesome',
    ];

    const ICONS = [
        0 => 'fas fa-poop',
        1 => 'far fa-thumbs-down',
        2 => 'far fa-meh',
        3 => 'far fa-thumbs-up',
        4 => 'fas fa-medal',
    ];

    protected $fillable = ['game_id', 'user_id'];

    public function getLabelAttribute()
    {
        return GameVote::LABELS[$this->score];
    }
}
