<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class NewsSubmission extends Model
{
    protected $table = 'news_submission';
    protected $primaryKey = 'news_submission_id';
    public $timestamps = false;
}
