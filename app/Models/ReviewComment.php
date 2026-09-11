<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class ReviewComment extends Comment
{
    use HasFactory;

    const SECTION = 'Reviews';

    protected $table = 'review_comments';

    protected $fillable = [
        'text', 'user_id', 'created_at', 'review_id',
    ];

    public function review()
    {
        return $this->belongsTo(Review::class);
    }

    public function getTargetAttribute()
    {
        return $this->review->game->name;
    }

    public function getTargetIdAttribute()
    {
        return $this->review_id;
    }
}
