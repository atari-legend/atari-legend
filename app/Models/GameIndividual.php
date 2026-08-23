<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class GameIndividual extends Pivot
{
    protected $table = 'game_individual';
    public $incrementing = true;

    public function individualRole()
    {
        return $this->belongsTo(IndividualRole::class);
    }
}
