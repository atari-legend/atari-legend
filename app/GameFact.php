<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class GameFact extends Model
{
    protected $table = 'game_fact';
    protected $primaryKey = 'game_fact_id';
    public $timestamps = false;
}
