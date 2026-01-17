<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InterviewText extends Model
{
    protected $table = 'interview_text';
    protected $primaryKey = 'interview_text_id';
    public $timestamps = false;

    protected $fillable = ['interview_id', 'interview_text', 'interview_date', 'interview_intro', 'interview_chapters'];

    protected $casts = [
        'interview_date' => 'datetime:timestamp',
    ];
}
