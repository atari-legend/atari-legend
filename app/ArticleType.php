<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ArticleType extends Model
{
    protected $table = 'article_type';
    protected $primaryKey = 'article_type_id';
    public $timestamps = false;
}
