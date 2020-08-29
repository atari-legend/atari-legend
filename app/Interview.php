<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Interview extends Model
{
    protected $table = "interview_main";
    protected $primaryKey = "interview_id";
    public $timestamps = false;

    public function user()
    {
        return $this->belongsTo(User::class, "user_id");
    }

    public function individual()
    {
        return $this->belongsTo(Individual::class, "ind_id");
    }

    public function text()
    {
        // FIXME: The DB structure actually allows many
        return $this->hasOne(InterviewText::class, "interview_text_id");
    }

    public function screenshots()
    {
        return $this->hasMany(ScreenshotGame::class, "screenshot_id");
    }
}
