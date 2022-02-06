<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Trivia extends Model
{
    protected $primaryKey = 'trivia_id';
    public $timestamps = false;

    protected $fillable = ['trivia_text'];
}
