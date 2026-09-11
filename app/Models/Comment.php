<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * What a game, article, interview and review comment have in common. Each lives
 * in its own table, so the table a comment is in is what says which section it
 * is on -- there is no type to work out at run time.
 */
abstract class Comment extends Model
{
    /**
     * The changelog section comments on this kind of owner are filed under.
     */
    const SECTION = null;

    protected $fillable = [
        'text', 'user_id', 'created_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return string Name of the target of the comment. For a comment on a game
     *                it is the game name.
     */
    abstract public function getTargetAttribute();

    /**
     * @return int ID of the target of the comment
     */
    abstract public function getTargetIdAttribute();
}
