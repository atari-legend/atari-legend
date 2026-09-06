<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArticleScreenshotComment extends Model
{
    public $timestamps = false;
    protected $fillable = ['text'];
}
