<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class GameDeveloper extends Pivot
{
    public $incrementing = true;

    public function developerRole()
    {
        return $this->belongsTo(DeveloperRole::class);
    }
}
