<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArticleType extends Model
{
    use HasFactory;

    protected $table = 'article_type';
    public $timestamps = false;

    protected $fillable = ['article_type'];
}
