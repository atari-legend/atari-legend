<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class InterviewComment extends Comment
{
    use HasFactory;

    const SECTION = 'Interviews';

    protected $table = 'interview_comments';

    protected $fillable = [
        'text', 'user_id', 'created_at', 'interview_id',
    ];

    public function interview()
    {
        return $this->belongsTo(Interview::class);
    }

    public function getTargetAttribute()
    {
        return $this->interview->individual->name;
    }

    public function getTargetIdAttribute()
    {
        return $this->interview_id;
    }
}
