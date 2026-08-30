<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class GameIndividual extends Pivot
{
    public $incrementing = true;

    public function individualRole()
    {
        return $this->belongsTo(IndividualRole::class);
    }
}
