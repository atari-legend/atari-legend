<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ReviewScore extends Model
{
    protected $table = "review_score";
    protected $primaryKey = "review_score_id";
    public $timestamps = false;
}
