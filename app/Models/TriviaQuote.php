<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TriviaQuote extends Model
{
    protected $primaryKey = 'trivia_quote_id';
    public $timestamps = false;

    protected $fillable = ['trivia_quote'];
}
