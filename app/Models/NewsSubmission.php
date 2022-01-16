<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsSubmission extends Model
{
    protected $table = 'news_submission';
    protected $primaryKey = 'news_submission_id';
    public $timestamps = false;

    protected $casts = [
        'news_date' => 'datetime:timestamp',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
