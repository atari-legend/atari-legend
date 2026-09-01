<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TriviaQuote extends Model
{
    public $timestamps = false;

    protected $fillable = ['quote'];
}
