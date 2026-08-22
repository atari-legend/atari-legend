<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NewsSubmission extends Model
{
    use HasFactory;

    protected $table = 'news_submission';
    public $timestamps = false;

    protected $casts = [
        'news_date' => 'datetime:timestamp',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
